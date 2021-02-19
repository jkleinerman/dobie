#include <gpiod.h>
#include <stdio.h>
#include <fcntl.h>
#include <unistd.h>
#include <string.h>
#include <errno.h>
#include <stdlib.h>
#include <systemd/sd-journal.h>
#include <pthread.h>
#include <mqueue.h>
#include <unistd.h>
#include <signal.h>
#include <common.h>
#include <state_snsr.h>

int init_state_snsr(state_snsr_t * state_snsr_p, 
                struct gpiod_chip *chip, 
                unsigned int gpio_num, 
				unsigned int door_id,
				struct timespec *event_wait_time_p){
    
	sd_journal_print(LOG_NOTICE, "State sensor of door: %d using GPIO: %d\n",
	                 door_id, gpio_num);
	state_snsr_p->chip = chip;
	state_snsr_p->gpio_num = gpio_num;
	state_snsr_p->door_id = door_id;
	state_snsr_p->event_wait_time_p = event_wait_time_p;

    
}





int enable_state_snsr(state_snsr_t * state_snsr_p) {
	int ret = 0;
	state_snsr_p->b_line = gpiod_chip_get_line(state_snsr_p->chip, state_snsr_p->gpio_num);
	if (!state_snsr_p->b_line) {
		sd_journal_print(LOG_ALERT, "Error getting line of GPIO: %d for state sensor of door: %d\n",
		                 state_snsr_p->gpio_num, state_snsr_p->door_id);
		return -1;
    }

	ret = gpiod_line_request_both_edges_events(state_snsr_p->b_line, CONSUMER);
	if (ret < 0) {
		sd_journal_print(LOG_ALERT, "Failing to request event notification for state sensor of door: %d\n",
		                 state_snsr_p->door_id);
		return -1;
    }
	return ret;
}








void *run_state_snsr (void *arg_p){
	int ret;
	int current_state, new_state;
	state_snsr_t *state_snsr_p = (state_snsr_t*) arg_p;
    char q_msg [50];
	enable_state_snsr(state_snsr_p);
	
	current_state = ! gpiod_line_get_value(state_snsr_p->b_line);

	while ( !exit_flag ) {
		
		ret = gpiod_line_event_wait(state_snsr_p->b_line, state_snsr_p->event_wait_time_p);

		switch (ret) {
			case 1:
				ret = gpiod_line_event_read(state_snsr_p->b_line, &state_snsr_p->event);
				gpiod_line_release(state_snsr_p->b_line);
				usleep(BOUNCE_TIME);
				enable_state_snsr(state_snsr_p);
				new_state = ! gpiod_line_get_value(state_snsr_p->b_line);

				if (new_state != current_state) {
					current_state = new_state;
                    sd_journal_print(LOG_NOTICE, "State sensor: %d detected state: %d\n",
					                 state_snsr_p->door_id, current_state);
					pthread_mutex_lock(&mq_mutex);
                        sprintf(q_msg, "%d;0;state=%d", state_snsr_p->door_id, current_state);
						ret = mq_send(mq, q_msg, strlen(q_msg), 1);
						if ( ret == 0 )
							sd_journal_print(LOG_DEBUG, "SUCCESS Sending to queue: %s\n", q_msg);
						else
							sd_journal_print(LOG_ALERT, "ERROR Sending to queue: %s\n", q_msg);
					pthread_mutex_unlock(&mq_mutex);
				} 	
				else
					sd_journal_print(LOG_INFO, "Noise in state sensor: %d\n", state_snsr_p->door_id);
				break;

			case 0:
				sd_journal_print(LOG_DEBUG, "Thread of state sensor: %d checking for exit\n", state_snsr_p->door_id);
				break;

			default:
				sd_journal_print(LOG_WARNING, "Unknown error on thread of state sensor: %d\n", state_snsr_p->door_id);
		}

	}
	sd_journal_print(LOG_NOTICE, "Thread of state sensor: %d releasing GPIO: %d\n", state_snsr_p->door_id, state_snsr_p->gpio_num);
    gpiod_line_release(state_snsr_p->b_line);
	sd_journal_print(LOG_NOTICE, "Thread of state sensor: %d finished.", state_snsr_p->door_id);

}