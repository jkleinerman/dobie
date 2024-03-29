import logging

import socket
import json


from network import *
from config import *





class CtrllerMsger(object):

    '''
    This class is responsible of create the message to be sent to the controller
    '''
    def __init__(self, netMngr):
        '''
        Receive the netMngr to be able to call sendToCtrller method
        of this object
        '''
        self.netMngr = netMngr

        #Getting the logger
        self.logger = logging.getLogger(LOGGER_NAME)



    def addDoor(self, ctrllerMac, door):
        '''
        Receives the controller MAC and a dictionary with door parameters.
        With them it creates the message to send it to controller (to add)
        It gives the created message to the network manager thread.
        '''
        doorJson = json.dumps(door).encode('utf8')
        msg = CUD + b'S' + b'C' + doorJson + END 
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to add door")


    def updDoor(self, ctrllerMac, door):
        '''
        Receives the controller MAC and a dictionary with door parameters.
        With them it creates the message to send it to controller (to update).
        It gives the created message to the network manager thread.        
        '''
        doorJson = json.dumps(door).encode('utf8')
        msg = CUD + b'S' + b'U' + doorJson + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to update door")



    def delDoor(self, ctrllerMac, doorId):
        '''
        Receives the controller MAC and the door ID.
        With them it creates the message to send it to controller (to delete).
        It gives the created message to the network manager thread.        
        '''
        doorId = str(doorId).encode('utf8')
        msg = CUD + b'S' + b'D' + b'{"id": ' + doorId + b'}' + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to delete door")



    def openDoor(self, ctrllerMac, doorId):
        '''
        Send a message to the controller requesting to
        open the door with doorId.
        '''
        doorId = str(doorId).encode('utf8')
        msg = ROD + b'{"id": ' + doorId + b'}' + END
        #The exception that could throw this method is catched in "openDoor"
        #method of crud.py
        self.netMngr.sendToCtrller(msg, ctrllerMac)



    def addUnlkDoorSkd(self, ctrllerMac, unlkDoorSkd):
        '''
        Receives the controller MAC and a dictionary with unlock door
        schedule parameters.
        With them it creates the message to send it to controller (to add)
        It gives the created message to the network manager thread.
        '''
        unlkDoorSkdJson = json.dumps(unlkDoorSkd).encode('utf8')
        msg = CUD + b'U' + b'C' + unlkDoorSkdJson + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to add unlock door schedule")



    def updUnlkDoorSkd(self, ctrllerMac, unlkDoorSkd):
        '''
        Receives the controller MAC and a dictionary with unlock door
        schedule parameters.
        With them it creates the message to send it to controller (to update).
        It gives the created message to the network manager thread.
        '''
        unlkDoorSkdJson = json.dumps(unlkDoorSkd).encode('utf8')
        msg = CUD + b'U' + b'U' + unlkDoorSkdJson + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to update unlock door schedule")



    def delUnlkDoorSkd(self, ctrllerMac, unlkDoorSkdId):
        '''
        Receives the controller MAC and the unlock door schedule ID.
        With them it creates the message to send it to controller (to delete).
        It gives the created message to the network manager thread.
        '''
        unlkDoorSkdId = str(unlkDoorSkdId).encode('utf8')
        msg = CUD + b'U' + b'D' + b'{"id": ' + unlkDoorSkdId + b'}' + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to delete unlock door schedule")



    def addExcDayUds(self, ctrllerMac, excDayUds):
        '''
        Receives the controller MAC and a dictionary with exception day to
        unlock door by schedule parameters.
        With them it creates the message to send it to controller (to add)
        It gives the created message to the network manager thread.
        '''
        excDayUdsJson = json.dumps(excDayUds).encode('utf8')
        msg = CUD + b'E' + b'C' + excDayUdsJson + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to add exception day to unlock door by schedule")



    def updExcDayUds(self, ctrllerMac, excDayUds):
        '''
        Receives the controller MAC and a dictionary with exception day to
        unlock door by schedule parameters.
        With them it creates the message to send it to controller (to update).
        It gives the created message to the network manager thread.
        '''
        excDayUdsJson = json.dumps(excDayUds).encode('utf8')
        msg = CUD + b'E' + b'U' + excDayUdsJson + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to update exception day to unlock door by schedule")



    def delExcDayUds(self, ctrllerMac, excDayUdsId):
        '''
        Receives the controller MAC and a dictionary with exception day to
        unlock door by schedule ID.
        With them it creates the message to send it to controller (to delete).
        It gives the created message to the network manager thread.
        '''
        excDayUdsId = str(excDayUdsId).encode('utf8')
        msg = CUD + b'E' + b'D' + b'{"id": ' + excDayUdsId + b'}' + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to delete exception day to unlock door by schedule")



    def addAccess(self, ctrllerMac, access):
        '''
        Receives the controller MAC and access dictionary.
        The access dictionary has some person parameters.
        With them it creates the message to send it to controller (to add).
        It gives the created message to the network manager thread.
        '''
        accessJson = json.dumps(access).encode('utf8')
        msg = CUD + b'A' + b'C' + accessJson + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to add access")


    def updAccess(self, ctrllerMac, access):
        '''
        Receives the controller MAC and access dictionary.
        With them it creates the message to send it to controller (to update).
        It gives the created message to the network manager thread.
        '''
        accessJson = json.dumps(access).encode('utf8')
        msg = CUD + b'A' + b'U' + accessJson + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to update access")


    def delAccess(self, ctrllerMac, accessId):
        '''
        Receives the controller MAC and the access ID.
        With them it creates the message to send it to controller (to delete).
        It gives the created message to the network manager thread.
        '''
        accessId = str(accessId).encode('utf8')
        msg = CUD + b'A' + b'D' + b'{"id": ' + accessId + b'}' + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to delete access")


    def addLiAccess(self, ctrllerMac, liAccess):
        '''
        Receives the controller MAC and limited access dictionary.
        The limited access dictionary has some person parameters.
        With them it creates the message to send it to controller (to add).
        It gives the created message to the network manager thread.
        '''
        liAccessJson = json.dumps(liAccess).encode('utf8')
        msg = CUD + b'L' + b'C' + liAccessJson + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to add limited access")


    def updLiAccess(self, ctrllerMac, liAccess):
        '''
        Receives the controller MAC and limited access dictionary.
        With them it creates the message to send it to controller (to update).
        It gives the created message to the network manager thread.
        '''
        liAccessJson = json.dumps(liAccess).encode('utf8')
        msg = CUD + b'L' + b'U' + liAccessJson + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to update limited access")


    def delLiAccess(self, ctrllerMac, liAccessId):
        '''
        Receives the controller MAC and the limited access ID.
        With them it creates the message to send it to controller (to delete).
        It gives the created message to the network manager thread.
        '''
        liAccessId = str(liAccessId).encode('utf8')
        msg = CUD + b'L' + b'D' + b'{"id": ' + liAccessId + b'}' + END
        try:
            self.netMngr.sendToCtrller(msg, ctrllerMac)
        except CtrllerDisconnected:
            self.logger.warning("Controller disconnected to delete limited access")


    def updPerson(self, ctrllerMacsToUpdPrsn, person):
        '''
        Receives a list of controller MAC addresses to send the update person msg
        and a person dictionary to create the message.
        '''
        personJson = json.dumps(person).encode('utf8')
        msg = CUD + b'P' + b'U' + personJson + END

        for ctrllerMac in ctrllerMacsToUpdPrsn:
            try:
                self.netMngr.sendToCtrller(msg, ctrllerMac)
            except CtrllerDisconnected:
                self.logger.warning("Controller disconnected to update person")



    def delPerson(self, ctrllerMacsToDelPrsn, personId):
        '''
        Receives a list of controller MAC addresses to send the delete person msg.
        With the person ID creates the message to send to the controllers
        '''
        personId = str(personId).encode('utf8')
        msg = CUD + b'P' + b'D' + b'{"id": ' + personId + b'}' + END
        
        for ctrllerMac in ctrllerMacsToDelPrsn:
            try:
                self.netMngr.sendToCtrller(msg, ctrllerMac)
            except CtrllerDisconnected:
                self.logger.warning("Controller disconnected to delete person.")



    def requestReSendCruds(self, ctrllerMacsNotComm):
        '''
        Send a message to the controller requesting re sending uncommitted CRUDs.
        '''
        msg = RRC + END

        for ctrllerMac in ctrllerMacsNotComm:
            try:
                self.netMngr.sendToCtrller(msg, ctrllerMac)
            except CtrllerDisconnected:
                self.logger.warning("Controller disconnected to receive request re send CRUD.")




    def requestReProv(self, ctrllerMac):
        '''
        Send a message to the controller requesting to be reprovisioned entirely.
        '''
        msg = RRP + END
        #The exception that could throw this method is catched in "reProvController"
        #method of crud.py
        self.netMngr.sendToCtrller(msg, ctrllerMac)




    def poweroffCtrller(self, ctrllerMac):
        '''
        Send a message to the controller requesting to be shut down.
        '''
        msg = RPO + END
        #The exception that could throw this method is catched in "poweroffController"
        #method of crud.py
        self.netMngr.sendToCtrller(msg, ctrllerMac)
