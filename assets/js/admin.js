(function ($) {

    var pluginFolder = uav.pluginFolder;
    //user page
    var UAVUsers = {
        init: function () {
            this.cacheDOM();
            this.eventListeners();
        },
        cacheDOM: function () {
            this.syncFlag = false;
            this.$uavUserList = $('#uav-user-list');

            // single
            this.$singleResend = $('a.uav-user-resend-single');
            this.$singleDelete = $('a.uav-user-delete-single');
        },
        eventListeners: function () {
            this.$singleResend.on('click', this.singleResendActivation.bind(this));
            this.$singleDelete.on('click', this.singleDeleteUser.bind(this));
        },
        singleResendActivation: function (e) {

            // Send Activation Link
            e.preventDefault();

            //  prevent multiple click action
            if (this.syncFlag) {
                alert('One task is already running. Please wait...');
                return;
            } else {
                this.syncFlag = true;
            }

            if (confirm('Your are about to resend activation link to this user. Please confirm. ')) {

                var $uavEle = $(e.currentTarget);
                var uav_user_id = $uavEle.attr('data-uav-user-id');

                // simulate hover until the work is finished
                $uavEle.closest('.row-actions').addClass('force-show');

                $.ajax({
                    method: "POST",
                    dataType: "json",
                    url: ajaxurl,
                    context: this,
                    data: {
                        action: 'uav_user_resend_single',
                        nonce: uav.uav_ajax_nonce,
                        uav_user_id: uav_user_id
                    },
                    beforeSend: function () {
                        $('<img class="uav-processing-gif" src="' + pluginFolder + 'assets/processing-18px.gif">').insertAfter($uavEle);
                    }

                }).done(function (uav_return) {
                    if (uav_return.uav_status) {
                        $('.uav-processing-gif').remove();
                        $('<span class="uav-sgreen">Done</span>').insertAfter($uavEle);
                        $uavEle.closest('tr').find('td:last-child').text(uav_return.uav_resend_count);
                        setTimeout(function () {
                            $('.uav-sgreen').remove();
                            UAVUsers.syncFlag = false;
                        }, 1000)
                    } else {
                        alert(uav_return.uav_msg);
                        $uavEle.parent().find('img').remove();
                        UAVUsers.syncFlag = false;
                    }
                });

            } else {
                this.syncFlag = false;
                return false;
            }
        },
        singleDeleteUser: function (e) {

            //  prevent multiple click action
            if (this.syncFlag) {
                alert('One task is already running. Please wait...');
                return;
            } else {
                this.syncFlag = true;
            }


            if (confirm('Your are about to delete the user. Please confirm. ')) {

                var $uavEle = $(e.currentTarget);
                var uav_user_id = $uavEle.attr('data-uav-user-id');

                // simulate hover until the work is finished
                $uavEle.closest('.row-actions').addClass('force-show');

                $.ajax({
                    method: "POST",
                    dataType: "json",
                    url: ajaxurl,
                    data: {
                        action: 'uav_user_delete_single',
                        nonce: uav.uav_ajax_nonce,
                        uav_user_id: uav_user_id
                    },
                    beforeSend: function () {
                        $('<img src="' + pluginFolder + 'assets/processing-18px.gif">').insertAfter($uavEle);
                    }

                }).done(function (uav_return) {
                    console.log(uav_return);
                    if (uav_return.uav_status) {
                        console.log(uav_return);
                        setTimeout(function () {
                            $uavEle.closest('tr').hide('slow');
                        }, 200)
                    } else if (uav_return.uav_msg !== undefined) {
                        alert(uav_return.uav_msg);
                        $uavEle.parent().find('img').remove();
                    }
                    UAVUsers.syncFlag = false;
                });

            } else {
                this.syncFlag = false;
                return false;
            }
        }
    };

    $(function () {
        UAVUsers.init();
    });

})(jQuery);