YUI.add('moodle-report_monitoraccesses-selectstrips', function(Y) {

    var SELECTSTRIPS = function() {
        SELECTSTRIPS.superclass.constructor.apply(this, arguments);
    };
    Y.extend(SELECTSTRIPS, Y.Base, {

        initializer: function(config) {

            // Set toggle to months.
            Y.all('.monitoraccesses-month').on('click', function(e) {
                this.toggle_month(e.currentTarget);
            }, this);
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
            NAME: 'moodle-report_monitoraccesses-selectstrips'
        });

        M.report_monitoraccesses = M.report_monitoraccesses || {};
        M.report_monitoraccesses.init_selectstrips = function(config) {
            return new SELECTSTRIPS(config);
        };

}, '@VERSION@', { requires: ['base', 'node'] });
