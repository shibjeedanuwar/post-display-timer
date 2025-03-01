(function ($) {
    'use strict';

    class PostTimerHandler {
        constructor(postTimerData, $container) {
            this.validateTimerData(postTimerData);
            this.postTimerData = { ...postTimerData };
            this.options = this.postTimerData.options; // Cache options
            this.$container = $container;
            this.postId = this.postTimerData.post_id || 0;

            this.initializeDOMElements();
            this.initializeTimerProperties();
        }

        validateTimerData(data) {
            if (!data || typeof data !== 'object') {
                throw new Error('Invalid timer configuration');
            }
        }

        initializeDOMElements() {
            this.$countdown = this.$container.find(`#countdown-${this.postId}`);
            this.$nextContainer = this.$container.find(`#nextPostContainer-${this.postId}`);
            this.$startButton = this.$container.find(`#startButton-${this.postId}`);
            this.$countdownContainer = this.$container.find('.pvt-timer-countdown'); // Cache common container
        }

        initializeTimerProperties() {
            this.countdown = this.validateCountdown();
            this.countdownInterval = null;
            this.isTimerPaused = false;
            this.isCountdownUpdated = false;
        }

        validateCountdown() {
            const defaultCountdown = 60;
            let countdown = parseInt(this.options.set_count_timer, 10) || defaultCountdown;

            if (countdown <= 0) {
                countdown = defaultCountdown;
            }

            this.$countdown.text(countdown);
            return countdown;
        }

        startCountdown() {
            this.hideStartButton();
            this.trackCountdownStart();
            this.initializeCountdownInterval();
            this.$nextContainer.find('.pvt-timer-next-btn').show();
        }

        hideStartButton() {
            this.$startButton.hide();
        }

        trackCountdownStart() {
            this.performAjaxRequest('start_countdown')
                .then(() => {
                    // Handle success silently
                })
                .catch(() => {
                    // Handle error silently
                });
        }

        initializeCountdownInterval() {
            this.countdownInterval = setInterval(() => {
                if (!this.isTimerPaused && this.countdown > 0) {
                    this.updateCountdown();
                } else if (this.countdown <= 0) {
                    this.handleCountdownEnd();
                }
            }, 1000);
        }

        updateCountdown() {
            this.countdown--;
            this.isCountdownUpdated =true;
            this.$countdown.text(this.countdown);
        }

        handleCountdownEnd() {
            clearInterval(this.countdownInterval);
            this.isCountdownUpdated = false;

            this.trackCountdownEnd();
        }

        trackCountdownEnd() {
            this.performAjaxRequest('end_countdown')
                .then(response => {
                    if (this.options.hit_count >= parseInt(this.options.view_number_post) && response.data.pvt_complete_code) {
                        this.$countdown.text("YOUR CODE IS : " + response.data.pvt_complete_code);
                    }
                    this.$nextContainer.find('.pvt-timer-next-btn').prop('disabled', false);
                })
                .catch(() => {
                    this.$nextContainer.find('.pvt-timer-next-btn').prop('disabled', false);
                });
        }

        performAjaxRequest(action) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.postTimerData.admin_url,
                    method: 'POST',
                    data: {
                        action,
                        post_id: this.postId,
                        post_timer_nonce: this.postTimerData.nonce
                    },
                    success: resolve,
                    error: reject
                });
            });
        }

        checkTimerPreventionState() {
            if (this.options.multiple_tab == 1 && $('.pvt-timer-countdown:visible').length > 0) {
                this.disableTimerInCurrentTab();
                return false;
            }
            return true;
        }

        disableTimerInCurrentTab() {
            this.hideStartButton();
            $('<div>')
                .addClass('pvt-timer-multiple-tab-message')
                .text('Timer is already running.')
                .insertBefore(this.$countdown);
            this.$countdownContainer.hide();
        }

        initialize() {
            this.setupCountdownStart();
            this.setupPageVisibilityTracking();
            this.setupTabCloseTracking();
        }

        setupCountdownStart() {
            if (this.options.start_button == 1) {
                this.$startButton.on('click', () => {
                    this.performAjaxRequest('start_button_click')
                        .then(response => {
                            if (response.data.status) {
                                this.$countdown.text("It looks like you've already started the timer in another tab. Please close that tab and try again.");
                                setTimeout(() => this.$container.hide(), 9000);
                                return;
                            }
                            this.startCountdown();
                        });
                });
            } else {
                this.startCountdown();
            }
        }

        setupPageVisibilityTracking() {
            if (this.options.check_currentPage) {
                $(document).on('visibilitychange', () => {
                    this.isTimerPaused = document.hidden;
                });
            }
        }

        setupTabCloseTracking() {
            window.addEventListener('beforeunload', (event) => {
                if (this.isCountdownUpdated) {
                    event.preventDefault();
                    this.performAjaxRequest('close_current_tab');
                }
            });
        }
       
        static initializeAllTimers() {
            $('[id^="timer-count-post-"]').each(function () {
                const $container = $(this);
                const postId = $container.attr('id').replace('timer-count-post-', '');

                try {
                    const timerHandler = new PostTimerHandler(
                        { ...window.PostDisplayTimerData, post_id: postId },
                        $container
                    );
                    timerHandler.initialize();
                } catch (error) {
                    // Silent fail in production
                }
            });
        }
    }

    $(document).ready(() => {
        if (typeof window.PostDisplayTimerData === 'undefined') {
            return;
        }
        PostTimerHandler.initializeAllTimers();
    });
})(jQuery);