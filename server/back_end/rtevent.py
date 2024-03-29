import queue
import logging
import requests

import genmngr
from config import *


class RtEventMngr(genmngr.GenericMngr):
    '''
    This thread is created by the main thread.
    When the "message receiver thread" receives an event from the controller,
    or it receives a "keep alive" message detecting that a controller revives,
    or "life checker" thread detects that a controller died, the event is put
    in "toRtEvent" queue (attribute of this class).
    This thread gets the events from the queue and sends them to the events-live.js
    app running in nodejs via REST using a POST method.
    '''

    def __init__(self, exitFlag):

        #Invoking the parent class constructor, specifying the thread name,
        #to have a understandable log file.
        super().__init__('RtEventMngr', exitFlag)

        self.nodejsUrl = 'http://{}:{}/readevent'.format(NODEJS_HOST, NODEJS_PORT)

        self.toRtEvent = queue.Queue()



    def run(self):
        '''
        This is the main method of the thread. Most of the time it is blocked
        waiting for events coming from the "Message Receiver" thread.
        '''

        while True:
            try:
                #Blocking until Message Receiver or Life Checker thread sends
                #an event or EXIT_CHECK_TIME expires.
                event = self.toRtEvent.get(timeout=EXIT_CHECK_TIME)
                self.checkExit()

                try:
                    self.logger.debug("Sending to nodejs live event: {}".format(event))
                    requests.post(self.nodejsUrl, json=event, timeout=NODEJS_TOUT)
                except (requests.exceptions.ConnectionError, requests.exceptions.ReadTimeout):
                    self.logger.warning("Error trying to connect to nodejs")

            except queue.Empty:
                #Cheking if Main thread ask as to finish.
                self.checkExit()
