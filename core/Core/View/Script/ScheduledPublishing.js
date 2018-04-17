/**
 * USAGE:
 *
 * In controller.class.php
 * =======================
 * \JS::activate('schedule-publish-tooltip', array());
 *
 * In template file
 * ================
 * <a class="schedule_status active" href="javascript:void(0);"></a>
 * <a class="schedule_status inactive" href="javascript:void(0);"></a>
 * <a class="schedule_status scheduled active" href="javascript:void(0);"></a>
 * <a class="schedule_status scheduled inactive" href="javascript:void(0);"></a>
 */

cx.ready(function(){
    var tooltip = cx.jQuery('<div />')
                    .addClass('tooltip-message')
                    .appendTo('body');
    cx.jQuery('.schedule_status')
        .tooltip({
            tip: tooltip,
            position: 'top right',
            track: true,
            predelay: 200,
            onBeforeShow: function() {
                var statuses   = [],
                    objTrigger = this.getTrigger(),
                    objTip     = this.getTip();
                objTip.html('');

                if (objTrigger.hasClass('scheduled')) {
                    if (objTrigger.hasClass('active')) {
                        statuses.push(cx.variables.get('scheduledActive', 'core/View'));
                    } else {
                        statuses.push(cx.variables.get('scheduledInactive', 'core/View'));
                    }
                } else if (objTrigger.hasClass('active')) {
                    statuses.push(cx.variables.get('active', 'core/View'));
                } else {
                    statuses.push(cx.variables.get('inactive', 'core/View'));
                }

                if (statuses.length > 0) {
                    objTip.html(statuses.join('<br />'));
                }
            }
        });
});
