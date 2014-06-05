YUI.add('moodle-report_monitoraccesses-toggle', function(Y) {

    var TOGGLE = function() {
        TOGGLE.superclass.constructor.apply(this, arguments);
    };
    Y.extend(TOGGLE, Y.Base, {

        plusicon: '',
        minusicon: '',

        initializer: function(config) {

            this.plusicon = config.plusicon;
            this.minusicon = config.minusicon;

            // Set toggle action.
            Y.all('.hide-show-image').on('click', this.toggle_div, this);
        },

        // Toggles a div.
        toggle_div : function(e) {

            var targetid = e.currentTarget.get('id');
            var togglecontents = Y.one('#' + targetid.replace('togglehide', 'togglecontents'));

            // Toggle contents.
            if (togglecontents.hasClass('hidden')) {
                togglecontents.removeClass('hidden');

                // Change alt and image.
                e.currentTarget.setAttribute('alt', M.util.get_string('hide', 'moodle'));
                e.currentTarget.setAttribute('title', M.util.get_string('hide', 'moodle'));
                e.currentTarget.setAttribute('src', this.minusicon);

            } else {
                togglecontents.addClass('hidden');

                // Change alt and image.
                e.currentTarget.setAttribute('alt', M.util.get_string('show', 'moodle'));
                e.currentTarget.setAttribute('title', M.util.get_string('show', 'moodle'));
                e.currentTarget.setAttribute('src', this.plusicon);

            }

            e.preventDefault();
        }

    }, {
            NAME: 'moodle-report_monitoraccesses-toggle',
            ATTRS: {
                plusicon: {},
                minusicon: {}
            }
        });

        M.report_monitoraccesses = M.report_monitoraccesses || {};
        M.report_monitoraccesses.init_toggle = function(config) {
            return new TOGGLE(config);
        };

}, '@VERSION@', { requires: ['base', 'node'] });
