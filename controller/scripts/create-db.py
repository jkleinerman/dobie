#!/usr/bin/env python3

#from argparse import ArgumentParser
#import logging
#import logging.handlers
import sqlite3

from config import *

db = sqlite3.connect(DB_FILE)
cursor = db.cursor()
cursor.execute('PRAGMA foreign_keys = ON')



#----------------Person Table----------------#

cursor.execute('''
    CREATE TABLE Person (
        id          INTEGER PRIMARY KEY,
        cardNumber  INTEGER
    )
    '''
)

cursor.execute('''CREATE UNIQUE INDEX cardNumberIndex
                  ON Person (cardNumber)
               '''
)


#----------------Passage Table-----------------#


cursor.execute('''
    CREATE TABLE PssgGpios (
        id       INTEGER PRIMARY KEY,
        i0In     INTEGER, 
        i1In     INTEGER,
        o0In     INTEGER,
        o1In     INTEGER,
        bttnIn   INTEGER,
        stateIn  INTEGER,
        rlseOut  INTEGER,
        bzzrOut  INTEGER
    )
    '''
)



cursor.execute('''
    CREATE TABLE Passage (
        id       INTEGER PRIMARY KEY,
        pssgNum  INTEGER,
        rlseTime INTEGER,
        bzzrTime INTEGER,
        alrmTime INTEGER,
        FOREIGN KEY(pssgNum) REFERENCES PssgGpios(id) ON DELETE CASCADE
    )
    '''
)

cursor.execute('''CREATE UNIQUE INDEX pssgNumIndex
                  ON Passage (pssgNum)
               '''
)



#----------------Access Table-----------------#

cursor.execute('''
    CREATE TABLE Access (
        id          INTEGER PRIMARY KEY,
        pssgId      INTEGER,
        personId    INTEGER,
        allWeek     BOOLEAN,
        iSide       BOOLEAN,
        oSide       BOOLEAN,
        startTime   DATETIME,
        endTime     DATETIME,
        expireDate  DATETIME,
        FOREIGN KEY(personId) REFERENCES Person(id) ON DELETE CASCADE,
        FOREIGN KEY(pssgId) REFERENCES Passage(id) ON DELETE CASCADE
    )
    '''
)

#FOREIGN KEY(pssgId) REFERENCES Passage(id) ON DELETE CASCADE

cursor.execute('''CREATE UNIQUE INDEX pssgPersonIndex
                  ON Access (pssgId, personId)
               '''
)


cursor.execute('''
    CREATE TABLE LimitedAccess (
        id         INTEGER PRIMARY KEY,
        pssgId     INTEGER,
        personId   INTEGER,
        weekDay    INTEGER, 
        iSide      BOOLEAN,
        oSide      BOOLEAN,
        startTime  DATETIME,
        endTime    DATETIME,
        FOREIGN KEY(personId) REFERENCES Person(id) ON DELETE CASCADE,
        FOREIGN KEY(pssgId) REFERENCES Passage(id) ON DELETE CASCADE
    )
    '''
)

cursor.execute('''CREATE UNIQUE INDEX pssgPersonWeekDayIndex
                  ON LimitedAccess (pssgId, personId, weekDay)
               '''
)




cursor.execute('''
    CREATE TABLE Event (
        id          INTEGER PRIMARY KEY,
        pssgId      INTEGER,
        eventTypeId INTEGER,
        dateTime    DATETIME,
        latchId     INTEGER,   
        personId    INTEGER,
        side        BOOLEAN,
        allowed     BOOLEAN,
        notReasonId INTEGER,
        FOREIGN KEY(eventTypeId) REFERENCES EventType(id),
        FOREIGN KEY(latchId) REFERENCES Latch(id),
        FOREIGN KEY(notReasonId) REFERENCES NotReason(id)
   )
    '''
)


cursor.execute('''
    CREATE TABLE EventType (
        id          INTEGER PRIMARY KEY,
        description TEXT

    )
    '''
)



cursor.execute('''
    CREATE TABLE Latch (
        id          INTEGER PRIMARY KEY,
        description TEXT

    )
    '''
)


cursor.execute('''
    CREATE TABLE NotReason (
        id          INTEGER PRIMARY KEY,
        description TEXT

    )
    '''
)












db.commit()
db.close()
