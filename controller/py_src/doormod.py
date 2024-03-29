import threading
import logging
import datetime
import time
import sys
import gpiod

import database
import queue

import genmngr
from msgheaders import *
from config import *
import errno




class DoorError(Exception):

    def __init__(self, errorMessage):
        self.errorMessage = errorMessage

    def __str__(self):
        return self.errorMessage


class DoorNotConfigured(DoorError):
    pass



class Door(object):
    '''
    This object stores parameters of the door.
    There are some parameters that will be set once the object is
    created and won't change during the execution like the GPIO line
    used to lock the door and start the buzzer.
    In the other hand there are some parameters that can be updated
    or set to None when a CRUD is received like: doorId, snsrType,
    unlkTime, bzzrTime and alrmTime.
    '''

    def __init__(self, gpioChip, unlkOutGpio,
                 bzzrOutGpio, doorId, snsrType, unlkTime,
                 bzzrTime, alrmTime):

        #Getting the logger
        self.logger = logging.getLogger('Controller')

        self.unlkLine = gpioChip.get_line(unlkOutGpio)
        self.bzzrLine = gpioChip.get_line(bzzrOutGpio)

        try:
            self.unlkLine.request(CONSUMER)
            self.unlkLine.set_direction_output(False)

            self.bzzrLine.request(CONSUMER)
            self.bzzrLine.set_direction_output(False)

        except OSError:
            self.logger.error('Error trying to get GPIO lines for output')
            sys.exit(errno.EBUSY)


        #The following could be None when the door is not configured yet
        self.doorId = doorId
        self.snsrType = snsrType
        self.unlkTime = unlkTime
        self.bzzrTime = bzzrTime
        self.alrmTime = alrmTime


        #Event to know when the door was opened
        self.openDoor = threading.Event()
        #Event object to know when a door was opened
        #in a correct way by someone who has access
        self.accessPermit = threading.Event()
        # Lock and datetime object to know when the access was opened
        self.lockTimeAccessPermit = threading.Lock()
        self.timeAccessPermit = None
        # Event object to know when the "cleanerDoorMngr" thread is alive
        # to avoid creating more than once
        self.cleanerDoorMngrAlive = threading.Event()
        # Event object to know when the "starterAlrmMngrMngr" thread
        # is alive to avoid creating more than once
        self.starterAlrmMngrAlive = threading.Event()
        #Event to know when the door was unlocked by schedule
        self.unlkedBySkd = threading.Event()


    def unlock(self, trueOrFalse):
        '''
        This method unlock or lock the door.
        '''
        self.unlkLine.set_value(int(trueOrFalse))


    def startBzzr(self, trueOrFalse):
        '''
        This method start or stop the buzzer door.
        '''
        self.bzzrLine.set_value(int(trueOrFalse))



class Doors(dict):
    '''
    This object inherit from dict, it contains the amount of doors
    of the controller and it is hashed by the doorNum
    '''

    def __init__(self):

        #Getting the logger
        #self.logger = logging.getLogger('Controller')

        #Chip object
        self.gpioChip = gpiod.Chip(GPIO_CHIP_NAME, gpiod.Chip.OPEN_BY_NAME)

        #Database
        dataBase = database.DataBase(DB_FILE)
        parmsDoors = dataBase.getParmsDoors()

        for parmsDoor in parmsDoors:
            self[parmsDoor['doorNum']] = Door(self.gpioChip,
                                               parmsDoor['unlkOut'], parmsDoor['bzzrOut'],
                                               parmsDoor['doorId'], parmsDoor['snsrType'],
                                               parmsDoor['unlkTime'], parmsDoor['bzzrTime'],
                                               parmsDoor['alrmTime']
                                              )

    def __del__(self):
        '''
        Close the gpiochip descriptor
        '''
        #We can't call logger at this moment since the object logger
        #doesn't exist at this moment.
        #self.logger.info("Closing the GPIO Chip.")
        print("Closing GPIO chip")
        self.gpioChip.close()



    def getDoorNum(self, doorId):
        '''
        Get doorNum with doorId. It is used in procNet when
        receiving doorId from GUI to open the door
        '''

        for doorNum, door in self.items():
            if door.doorId == doorId:
                return doorNum

        raise DoorNotConfigured('Door not configured')


class CleanerDoorMngr(genmngr.GenericMngr):
    '''
    This thread stops the buzzer and close the door.
    It is created when the door is opened.
    It receives the times from the database.
    When the door is opened more than once consecutively, the time is prolonged.
    '''

    def __init__(self, door, exitFlag):

        #Door object to control de door
        self.door = door

        #Invoking the parent class constructor, specifying the thread name,
        #to have a understandable log file.
        super().__init__('CleanerDoorMngr_{}'.format(self.door.doorId), exitFlag)


    def run(self):
        '''
        This is the main method of the thread.
        It sleeps "EXIT_CHECK_TIME". Each time it awakes it calculates the time happened
        from the last access in the door. According with that time, it stops the buzzer
        and/or close the door. When both conditions happen, the thread finishes.
        '''

        alive = True

        while alive:

            time.sleep(EXIT_CHECK_TIME)
            self.checkExit()

            with self.door.lockTimeAccessPermit:
                elapsedTime = datetime.datetime.now() - self.door.timeAccessPermit

            elapsedTime = int(elapsedTime.total_seconds())

            if  elapsedTime >= self.door.unlkTime:
                #relocking the door if it shouldn't be kept unlocked by schedule
                if not self.door.unlkedBySkd.is_set():
                    self.logger.debug("Locking door {}.".format(self.door.doorId))
                    self.door.unlock(False)
                #Once the door was closed, if somebody opens the door, will be
                #considered an unpermitted access since the following event wil be cleared
                self.door.accessPermit.clear()

            #At the beginning the "if" was in the following way:
            #"if bzzrStarted and elapsedTime >= self.bzzrTime" to avoid calculating time
            #in each iteration, but then we have to remove the first part of the "if" because
            #sometimes when the buzzer was stopped and the door was not closed yet and in
            #this moment someone passes again, the buzzer was started again and it will never
            #be cleared since the "bzzrStarted" variable was set to "False"
            #The same could happen with "unlkTime" if it will be lesser than "bzzrTime".

            if  elapsedTime >= self.door.bzzrTime:
                self.logger.debug("Stopping the buzzer on door {}.".format(self.door.doorId))
                self.door.startBzzr(False)

            if elapsedTime >= self.door.unlkTime and elapsedTime >= self.door.bzzrTime:
                self.logger.debug("Finishing CleanerDoorMngr on door {}".format(self.door.doorId))
                alive = False

        #Notifying that this thread has died
        self.door.cleanerDoorMngrAlive.clear()





class StarterAlrmMngr(genmngr.GenericMngr):
    '''
    This thread starts the buzzer when the door remains opened
    for more than
    It receives the times from the database.
    When the door is opened more than once consecutively, the time is prolonged.
    '''

    def __init__(self, door, toEvent, exitFlag):

        #Dictionary with variables to control the door
        self.door = door

        #Queue to send events to Event Manager when the alarm start
        self.toEvent = toEvent

        #Invoking the parent class constructor, specifying the thread name,
        #to have a understandable log file.
        super().__init__('StarterAlrmMngr_{}'.format(self.door.doorId), exitFlag)


    def run(self):
        '''
        This is the main method of the thread.
        It sleeps "EXIT_CHECK_TIME". Each time it awakes it calculates the time happened
        from the last access in the door. If that time is greater than "alrmTime",
        it turns on the buzzer acting as an alarm
        '''

        alive = True

        while alive and self.door.openDoor.is_set():

            time.sleep(EXIT_CHECK_TIME)
            self.checkExit()

            with self.door.lockTimeAccessPermit:
                nowDateTime = datetime.datetime.now()
                elapsedTime = nowDateTime - self.door.timeAccessPermit

            elapsedTime = int(elapsedTime.total_seconds())

            if elapsedTime >= self.door.alrmTime:
                self.logger.debug("Starting the alarm on door {}.".format(self.door.doorId))
                self.door.startBzzr(True)


                dateTime = nowDateTime.strftime('%Y-%m-%d %H:%M')
                event = {'doorId' : self.door.doorId,
                         'eventTypeId' : EVT_REMAIN_OPEN,
                         'dateTime' : dateTime,
                         'doorLockId' : None,
                         'cardNumber' : None,
                         'side' : None,
                         'allowed' : None,
                         'denialCauseId' : None
                        } #Event object to know when a door was opened
                          #in a correct way by someone who has access
                #Sending the event to the "Event Manager" thread
                self.toEvent.put(event)

                alive = False

        #Notifying that this thread has died
        self.door.starterAlrmMngrAlive.clear()




class UnlkDoorSkdMngr(genmngr.GenericMngr):
    '''
    This thread opens and closes all the doors of the controller
    using the information retrieved from OpenDoorsSkd table.
    It generates events when the door change the state.
    As this thread is synchronous since it wakes up every
    "EXIT_CHECK_TIME", the responsibility of sending keep alive
    messages to the server, was added to it.
    '''


    def __init__(self, netMngr, toEvent, lockDoors, doors, exitFlag):

        #Invoking the parent class constructor, specifying the thread name,
        #to have a understandable log file.
        super().__init__('UnlkDoorSkdMngr', exitFlag)

        #This is used to send Keep alive messages to the server in a synchronous way
        self.netMngr = netMngr

        #Queue to send events to Event Manager thread
        self.toEvent = toEvent

        #Doors dictionary and is lock to protect it.
        self.lockDoors = lockDoors
        self.doors = doors

        #Calculating the number of iterations before checking if there are doors
        #to be opened or closed by schedule.
        self.ITERS_CHK_OPEN_DOORS = CHECK_OPEN_DOORS_MINS * 60 // EXIT_CHECK_TIME

        #This is the actual iteration. This value is incremented in each iteration
        #and is initializated to 0.
        self.iterChkOpenDoors = 0


        #Calculating the number of iterations before sending keep alive message
        #to the server
        self.ITERS_SEND_KAL = KEEP_ALIVE_TIME // EXIT_CHECK_TIME

        #This is the actual iteration. This value is incremented in each iteration
        #and is initializated to 0.
        self.iterSendKal = 0




    def run(self):
        '''
        Every "EXIT_CHECK_TIME", it increments "self.iterChkOpenDoors" and "self.iterSendKal".
        When "self.iterChkOpenDoors" reaches "self.ITERS_CHK_OPEN_DOORS", it checks if there
        are doors to be opened by schedule.
        When "self.iterSendKal" reaches "self.ITERS_SEND_KAL", it sends keep alive message
        to the server.
        Every "EXIT_CHECK_TIME" it also checks if the main thread asks to finsih using
        "checkExit" method.
        '''

        #First of all, the database should be connected by the execution of this thread
        dataBase = database.DataBase(DB_FILE)


        while True:
            #Blocking until EXIT_CHECK_TIME expires
            time.sleep(EXIT_CHECK_TIME)
            self.checkExit()

            if self.iterChkOpenDoors >= self.ITERS_CHK_OPEN_DOORS:
                logMsg = 'Checking Unlock Door Schedule.'
                self.logger.debug(logMsg)
                self.iterChkOpenDoors = 0

                with self.lockDoors:

                    doorsToUnlkBySkd = dataBase.getDoorsToUnlkBySkd()
                    for doorNum, door in self.doors.items():
                        doorId = door.doorId

                        if doorId in doorsToUnlkBySkd and not door.unlkedBySkd.is_set():
                            logMsg = 'Door: {} is opened by schedule'.format(doorId)
                            self.logger.debug(logMsg)
                            door.unlkedBySkd.set()
                            door.unlock(True)

                            dateTime = datetime.datetime.now().strftime('%Y-%m-%d %H:%M')
                            event = {'doorId' : doorId,
                                     'eventTypeId' : EVT_OPEN_SKD,
                                     'dateTime' : dateTime,
                                     'doorLockId' : None,
                                     'cardNumber' : None,
                                     'side' : None,
                                     'allowed' : None,
                                     'denialCauseId' : None
                                    }
                            self.toEvent.put(event)


                        elif doorId not in doorsToUnlkBySkd and door.unlkedBySkd.is_set():
                            logMsg = 'Door: {} is closed by schedule'.format(doorId)
                            self.logger.debug(logMsg)
                            door.unlkedBySkd.clear()
                            door.unlock(False)

                            dateTime = datetime.datetime.now().strftime('%Y-%m-%d %H:%M')
                            event = {'doorId' : doorId,
                                     'eventTypeId' : EVT_CLOSE_SKD,
                                     'dateTime' : dateTime,
                                     'doorLockId' : None,
                                     'cardNumber' : None,
                                     'side' : None,
                                     'allowed' : None,
                                     'denialCauseId' : None
                                    }
                            self.toEvent.put(event)

            else:
                self.iterChkOpenDoors += 1



            if self.iterSendKal >= self.ITERS_SEND_KAL:
                logMsg = 'Sending Keep Alive Message to server.'
                self.logger.debug(logMsg)
                self.iterSendKal = 0

                self.netMngr.sendToServer(KAL + END)


            else:
                self.iterSendKal += 1
