YUI.add('moodle-report_monitoraccesses-selectstrips', function(Y) {

    var SELECTSTRIPS = function() {
        SELECTSTRIPS.superclass.constructor.apply(this, arguments);
    };
    Y.extend(SELECTSTRIPS, Y.Base, {

        plusicon: '',
        minusicon: '',

        initializer: function(config) {

            this.plusicon = config.plusicon;
            this.minusicon = config.minusicon;

            // Set toggle to strips.
            for (var nstrip = 1; nstrip <= config.nstrips; nstrip++) {
                var strip = Y.one('#togglehide_' + nstrip);
                strip.on('click', this.toggle_strip, this);
            }

            // Set toggle to months.
            Y.all('.monitoraccesses-month').on('click', function(e) {
                this.toggle_month(e.currentTarget);
            }, this);
        },

        // Toggles a strip.
        toggle_strip: function(e) {

            var stripid = e.currentTarget.get('id');
            var dates = Y.one('#' + stripid.replace('togglehide', 'dates'));

            // Toggle dates.
            if (dates.hasClass('hidden')) {
                dates.removeClass('hidden');

                // Change alt and image.
                e.currentTarget.setAttribute('alt', M.util.get_string('hide', 'moodle'));
                e.currentTarget.setAttribute('title', M.util.get_string('hide', 'moodle'));
                e.currentTarget.setAttribute('src', this.minusicon);

            } else {
                dates.addClass('hidden');

                // Change alt and image.
                e.currentTarget.setAttribute('alt', M.util.get_string('show', 'moodle'));
                e.currentTarget.setAttribute('title', M.util.get_string('show', 'moodle'));
                e.currentTarget.setAttribute('src', this.plusicon);

            }

            e.preventDefault();
        },

        toggle_month: function(element) {

            // stripid_year_month.
            var monthdata = element.getAttribute('id').split('_');
            var stripid = monthdata[0];
            var year = monthdata[1];
            var month = monthdata[2];

            var elementname;
            var elements = new Array();
            var elementDate;
            var weekday;

            var checkboxes = document.getElementsByTagName("input");
            var elementformat = "date_" + stripid + "_" + year + "_" + month;

            for(var i = 0; i < checkboxes.length; i++) {

                // If it is one of the month days
                elementname = checkboxes[i].name;
                if (elementname.search(elementformat) != -1) {

                    // Don't change the weekend days
                    elements = elementname.split('_');
                    elementDate = new Date(elements[2], (elements[3] - 1), elements[4]);
                    weekday = elementDate.getDay();

                    // Only non weekend days must change (O = sunday, 6 = saturday)
                    if (weekday != 6 && weekday != 0) {

                        // If the month checkbox is checked we check all week days.
                        if (element.get('checked')) {
                            checkboxes[i].checked = true;
                        } else {
                            checkboxes[i].checked = false;
                        }
                    }
                }
            }
        }

    }, {
            NAME: 'moodle-report_monitoraccesses-selectstrips',
            ATTRS: {
                nstrips: {},
                plusicon: {},
                minusicon: {}
            }
        });

        M.report_monitoraccesses = M.report_monitoraccesses || {};
        M.report_monitoraccesses.init_selectstrips = function(config) {
            return new SELECTSTRIPS(config);
        };

}, '@VERSION@', { requires: ['base', 'node'] });
