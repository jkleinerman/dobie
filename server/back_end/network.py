import threading
import logging
import datetime
import time
import os

import select
import socket
import json

import database
import queue

import genmngr
from config import *


import sys



int_CON  = 0x01
int_RCON = 0x02
int_EVT  = 0x03
int_REVT = 0x04
int_EVS  = 0x05
int_REVS = 0x06
int_CUD  = 0x07
int_RCUD = 0x08
int_END  = 0x1F


CON  = bytes([int_CON])
RCON = bytes([int_RCON])
EVT  = bytes([int_EVT])
REVT = bytes([int_REVT])
EVS  = bytes([int_EVS])
REVS = bytes([int_REVS])
CUD  = bytes([int_CUD])
RCUD = bytes([int_RCUD])
END  = bytes([int_END])





class CtrllerDisconnected(Exception):
    pass


class TimeOutConnectionMsg(Exception):
    pass


class UnknownController(Exception):
    pass






class Unblocker(object):
    '''
    This class declares a pipe in its constructor.
    It stores read and write file descriptor as attributes.
    -The getFd method returns the read file descriptor which is registered to be monitored 
    by poll().
    -The unblock method write a dummy byte (0) to generate a event to wake up the poll()
    -The receive method reads this dummy byte because if it is not read, the next call
    to poll(), will wake up again(). We are reading more than one byte (ten bytes), 
    for the case of two consecutives calls generates two wake ups. (Not sure if it has sense)
    '''
    
    def __init__(self):
        self.readPipe, self.writePipe = os.pipe()

    def getFd(self):
        return self.readPipe

    def unblock(self):
        os.write(self.writePipe, b'0')

    def receive(self):
        os.read(self.readPipe, 10)

    



class NetMngr(genmngr.GenericMngr):

    '''
    This thread receives the events from the main thread, tries to send them to the server.
    When it doesn't receive confirmation from the server, it stores them in database.
    '''
    def __init__(self, dbMngr, exitFlag):

        #Invoking the parent class constructor, specifying the thread name, 
        #to have a understandable log file.
        super().__init__('NetMngr', exitFlag)

        #Buffer to receive bytes from server (should be changed to dict for each connection)
        #It is used in POLLIN event
        self.inBuffer = b''

        #Buffer to send bytes to server (should be changed to dict for each connection)
        #It is used in POLLOUT event
        self.outBuffer = b''

        #Queue used to send Events and CRUD confirmation to dbMngr
        self.dbMngr = dbMngr
        #self.netToDb = netToDb

        #In this queue, "event" and "reSender" threads put the messages
        #and "netMngr" gets them to send to the server.
        #We are using a queue because we can not assure the method
        #"sendEvent()" will not be called again before the "poll()" method wakes
        #up to send the bytes. If we do not do this, we would end up with a mess
        #of bytes in the out buffer.
        #(Not sure if this is necessary or it is the best way to do it)
        self.outBufferQue = queue.Queue()

        #Poll Network Object to monitor the sockets
        self.netPoller = select.poll()

        #Lock to protect access to "netPoller" 
        self.lockNetPoller = threading.Lock()

        #Unblocker object to wake up the thread blocked in poll() call
        self.unblocker = Unblocker()
        self.unBlkrFd = self.unblocker.getFd()

        #Registering above pipe in netPoller object
        self.netPoller.register(self.unBlkrFd)

        #Creating the socket listener
        self.listenerSckt = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.listenerSckt.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.listenerSckt.bind((BIND_IP, BIND_PORT))
        self.listenerSckt.listen(SIM_CONNECTIONS)
        #Saving the listener socket file descriptor
        self.listenerScktFd = self.listenerSckt.fileno()

        #Registering socket listener in netPoller object
        self.netPoller.register(self.listenerSckt, select.POLLIN)

        #Dictionary indexed by socket controller file descriptors. Each element
        #of this dictionary is another dictionary with the socket,
        self.fdConns = {}

        #Dictionary to get the socket file descriptor with the ip address
        self.addrFd = {}
        
 


    #---------------------------------------------------------------------------#

    def sendEvent(self, event):
        '''
        This method is called by the "eventMngr" thread each time it receives 
        an event from the "main" thread.
        It receives a dictionary as an event which is converted to a JSON bytes
        to create the event message to send to the server.
        The sequence of bytes is stored in a queue, the "netPoller" is modified
        to tell the "netMngr" there is bytes to send and the "netMngr" thread is
        unblocked from the "poll()" method using the "unblocker" object. 
        Once this happens, the "netMngr" thread pulls the message from queue and 
        send them to the server.       
        '''

        if self.connected.is_set():

            #Converting dictionary to JSON bytes
            jsonEvent = json.dumps(event).encode('utf8')
            #Adding headers at beggining and end
            outMsg = EVT + jsonEvent + END

            #Writing the messages in a queue because we can not assure the method
            #"sendEvent()" will not be called again before the "poll()" method wakes
            #up to send the bytes. If we do not do this, we would end up with a mess
            #of bytes in the out buffer.
            #(Not sure if this is necessary or it is the best way to do it)
            self.outBufferQue.put(outMsg)

            with self.lockNetPoller:
                try:
                    #Modifying "netPoller" to notify there is a message to send.
                    self.netPoller.modify(self.srvSock, select.POLLOUT)
                    #Unblocking the "netMngr" thread from the "poll()" method.
                    self.unblocker.unblock()
                except FileNotFoundError:
                    #This exception could happen if it is received a null byte (b'')
                    #in POLLIN evnt, the socket is closed and "eventMngr" calls this
                    #method before POLLNVAL evnt happens to clean "self.connected".
                    #(Not sure if this can occur)                    
                    self.logger.debug('The socket was closed and POLLNVALL was not captured yet.')
            
        else:
            self.logger.debug('Can not send event, server is disconnected.')



    #---------------------------------------------------------------------------#


    def ackEvent(self):
        '''
        This method answer to a event sent by a controller with an OK
        '''
        pass


    def reSendEvents(self, eventList):
        '''
        This method is called by the "reSender" thread.
        It receives a list of dictionaries as an events. Each event is converted
        to a JSON bytes delimitted by headers to create the events message to send
        to the server.
        The sequence of bytes is stored in a queue, the "netPoller" is modified
        to tell the "netMngr" there is bytes to send and the "netMngr" thread is
        unblocked from the "poll()" method using the "unblocker" object. 
        Once this happens, the "netMngr" thread pulls the message from queue and 
        send them to the server.
        See the comments in "SendEvent" message. 
        '''

        if self.connected.is_set():

            outMsg = b''
            for event in eventList:
                jsonEvent = json.dumps(event).encode('utf8')
                outMsg += EVS + jsonEvent
            outMsg += END

            self.outBufferQue.put(outMsg)
            with self.lockNetPoller:
                try:
                    self.netPoller.modify(self.srvSock, select.POLLOUT)
                    self.unblocker.unblock()
                except FileNotFoundError:
                    self.logger.debug('The socket was closed and POLLNVALL was not captured yet.')

        else:
            self.logger.debug('Can not re-send event, server is disconnected.')



    #---------------------------------------------------------------------------#

    def procRecMsg(self, msg):
        '''
        This method is called by the main "run()" method when it receives bytes
        from the server. This happens in POLLIN evnts branch.
        It process the message and delivers it to the corresponding thread 
        according to the headers of the message.
        '''
        print('Entering')
        #This is a response to an event sent to the server
        #It should be delivered to "eventMngr" thread.
        if msg.startswith(EVT):
            print('1')
            response = msg.strip(EVT+END)
            response = response.decode('utf8')
            self.netToEvent.put(response)

        #This is a response to a set of re-sent events sent to the server
        #It should be delivered to "reSender" thread.
        elif msg.startswith(REVS):
            print('2')
            response = msg.strip(REVS+END)
            response = response.decode('utf8')
            self.netToReSnd.put(response)
        


    #---------------------------------------------------------------------------#






    def recvConMsg(self, ctrlSckt, timeToWait):
        '''
        This method receive the response to Connection Message.
        It waits until all response comes
        '''
        if not ctrlSckt:
            raise ControllerNotConnected

        ctrlSckt.settimeout(timeToWait)

        completeMsg = b''
        completed = False
        while not completed:

            try:
                msg = ctrlSckt.recv(REC_BYTES)
                if not msg:
                    raise CtrllerDisconnected
                self.logger.debug('The controller send {} as CON message'.format(msg))
                self.checkExit()
            except socket.timeout:
                raise TimeOutConnectionMsg

            completeMsg += msg
            if completeMsg.endswith(END):
                completed = True

        msgContent = completeMsg.strip(CON+END).decode('utf8')

        return msgContent



    def sendRespConMsg(self, ctrlSckt, ctrllerMac):
        '''
        '''

        if self.dbMngr.isValidCtrller(ctrllerMac):
            ctrlSckt.sendall(RCON + b'OK' + END)
        else:
            ctrlSckt.sendall(RCON + b'NO' + END)
            raise UnknownController










    def run(self):
        '''
        This is the main method of the thread.
        When the controller is connected to the server, this method is blocked most of
        the time in "poll()" method waiting for bytes to go out or incoming bytes from 
        the server or  a event produced when the socket is broken or disconnect.
        When there is no connection to the server, this method tries to reconnect to 
        the server every "RECONNECT_TIME"
        '''

        while True:

            for fd, pollEvnt in self.netPoller.poll(NET_POLL_MSEC):

                #This will happen when the "event" thread or "reSender"
                #thread puts bytes in the "outBufferQue" and they want to notify
                #this thread to wake up from "poll()"
                if fd == self.unBlkrFd:
                    self.unblocker.receive()
                    continue


                if fd == self.listenerScktFd:
                    ctrlSckt, address = self.listenerSckt.accept()

                    try:
                        ctrllerMac = self.recvConMsg(ctrlSckt, WAIT_RESP_TIME)
                        self.sendRespConMsg(ctrlSckt, ctrllerMac)

                        self.logger.info('Accepting connection from: {}'.format(address))
                        ctrlScktFd = ctrlSckt.fileno()

                        self.fdConns[ctrlScktFd] = {'socket': ctrlSckt,
                                                    'inBuffer': b'',
                                                    'outBufferQue': queue.Queue()
                                                   }
                        self.addrFd = {ctrllerMac: ctrlScktFd}
                        print(self.addrFd)
    
                        self.netPoller.register(ctrlSckt, select.POLLIN)



                    except CtrllerDisconnected:
                        self.logger.warning('The controller at: {} disconnected'.format(address))
                        ctrlSckt.close()

                    except TimeOutConnectionMsg:
                        self.logger.warning('The controller does not complete CON message.')
                        ctrlSckt.close()

                    except UnknownController:
                        self.logger.warning('Unknown controller trying to connect.')
                        ctrlSckt.close()




                #This will happen when the server sends to us bytes.
                elif pollEvnt & select.POLLIN:
                    print('PLLIN')
                    ctrlSckt = self.fdConns[fd]['socket']
                    recBytes = ctrlSckt.recv(REC_BYTES)
                    self.logger.debug('Receiving: {}'.format(recBytes))

                    #Receiving b'' means the controller closed the connection
                    #On this situation we should close the socket and the
                    #next call to "poll()" will throw a POLLNVAL event
                    if not recBytes:
                        ctrlSckt.close()
                        continue

                    #We should receive bytes until we receive the end of
                    #the message


                    msg = self.fdConns[fd]['inBuffer'] + recBytes
                    if msg.endswith(END):
                        self.procRecMsg(msg)
                    else:
                        self.fdConns[fd]['inBuffer'] = msg


                #This will happen when "event" thread or "reSender" thread
                #puts bytes in "outBufferQue", modifying the "netPoller"
                #to send bytes.
                elif pollEvnt & select.POLLOUT:
                    try:
                        #We can have more than one message in the "outBufferQue"
                        #to send, because we can have more than one call to
                        #"sendEvent()" or "reSendEvents()" before the POLLOUT event
                        #happens. For this reason we should empty the "outBufferQue"
                        #sending all the messages. Then, if we have another POLLOUT
                        #event and the queue is empty, nothing will happen.
                        while True:
                            self.outBuffer = self.outBufferQue.get(block = False)
                            self.logger.debug('Sending: {}'.format(self.outBuffer))
                            self.srvSock.sendall(self.outBuffer)
                    except queue.Empty:
                        #No more messages to send in "outBufferQue"
                        pass
                    #Once we finished sending all the messages, we should modify the
                    #"netPoller" object to be able to receive bytes again.
                    with self.lockNetPoller:
                        self.netPoller.modify(self.srvSock, select.POLLIN)


                #This will happen when the server closes the socket or the 
                #connection with the server is broken
                elif pollEvnt & (select.POLLHUP | select.POLLERR | select.POLLNVAL):
                    print('planvll')
                    self.logger.info('The connection with server was broken.')
                    with self.lockNetPoller:
                        #Unregistering the socket from the "netPoller" object
                        self.netPoller.unregister(fd)
                    #Setting "connected" to False (this will break the while loop)
                    self.connected.clear()

            #Cheking if Main thread ask as to finish.
            self.checkExit()



    
