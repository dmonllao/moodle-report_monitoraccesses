YUI.add('moodle-report_monitoraccesses-courseslist', function(Y) {

    var COURSESLIST = function() {
        COURSESLIST.superclass.constructor.apply(this, arguments);
    };
    Y.extend(COURSESLIST, Y.Base, {

        initializer : function(config) {

            var submit = Y.one('#id_submitcourses');
            submit.on('click', this.display_users, this);
        },

        display_users: function(e) {

            e.preventDefault();

            var usersdiv = Y.one("#id_users");
            usersdiv.setHTML("");

            var params = "action=userslist&output=simple";
            var coursesparam = "";

            // Send the selected courses
            var thereareusers = false;
            var courses = document.getElementsByTagName("input");
            for (var i = 0; i < courses.length; i++) {
                if (courses[i].type == 'checkbox') {

                    // Ensure that the checkbox are checked
                    if (courses[i].checked == true) {
                        coursesparam += "&"+courses[i].name+"=1";
                    }
                }
            }

            if (coursesparam != "") {
                cfg = {
                    method: 'post',
                    data: params + coursesparam,
                    context : self,
                    on: {
                        success: function(id, o) {

                            var usersdiv = document.getElementById("id_users");
                            usersdiv.innerHTML = o.responseText;
                        },
                        failure: function(id, o) {
                            console.log(o.statusText, "error");
                        }
                    },
                };
                Y.io('index.php', cfg)
            }
        }

    }, {
        NAME : 'moodle-report_monitoraccesses-courseslist'
    });

    M.report_monitoraccesses = M.report_monitoraccesses || {};
    M.report_monitoraccesses.init_courseslist = function(config) {
        return new COURSESLIST(config);
    };

}, '@VERSION@', { requires: ['base', 'node', 'io'] });
