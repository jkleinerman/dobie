import logging

SIM_PERSON_QUANT = 5000
SIM_LIM_ACCESS_QUANT = 500


DB_FILE = 'access.db'
QUEUE_FILE = '/door_iface_queue'


LOGGING_FILE ='logevents.log'

SERVER_IP = '127.0.0.1'
SERVER_PORT = 7979

EXIT_CHECK_TIME = 2
WAIT_RESP_TIME = 2

RE_SEND_TIME = 5

RECONNECT_TIME = 2

REC_BYTES = 4096

NET_POLL_MSEC = 1000


BIND_IP = '0.0.0.0'
BIND_PORT = 4440
SOCK_BUF_LEN = 1024
SIM_DEV_CONNECTIONS = 10

MGT_BIND_IP = '0.0.0.0'
MGT_BIND_PORT = 4441
MGT_SOCK_BUF_LEN = 512
MGT_SIM_DEV_CONNECTIONS = 10

DEFAULT_SEND_RETRIES = 3
DEFAULT_TIME_WAIT_RESPONSE = 10


KEEP_ALIVE_TIME = 5

loggingLevel = logging.DEBUG

