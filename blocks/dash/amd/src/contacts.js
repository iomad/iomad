define(['core/str', 'core/modal_factory', 'core/modal_events',
'core/fragment', 'core/templates', 'core/notification', 'core_message/toggle_contact_button'],
    function(Str, Modal, ModalEvents, Fragment, Templates, Notification, Contact) {

    return {
        init: function(contextID) {
            var groupModal = document.getElementsByClassName('contact-widget-viewgroup');
            var contactUser;
            Array.from(groupModal).forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = e.target.closest('a');
                    contactUser = target.getAttribute('data-userid');
                    Modal.create({
                        title: Str.get_string('groups', 'core')
                    }).then(function(modal) {
                        modal.show();

                        var args = JSON.stringify({contactuser: contactUser});
                        var params = {widget: 'contacts', method: 'load_groups', args: args};
                        Fragment.loadFragment('block_dash', 'loadwidget', contextID, params).then((html, js) => {
                            modal.setBody(html);
                            Templates.runTemplateJS(js);
                            return html;
                        }).catch(Notification.exception);

                        modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });
                        return modal;
                    }).catch(Notification.exception);
                });
            });

            var contactModal = document.getElementsByClassName('toggle-contact-button');
            Array.from(contactModal).forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (e.target.dataset.userid != undefined) {
                        Contact.enhance(e.target);
                    }
                });
            });
        }
    };
});
