
import Pending from 'core/pending';
import {get_string as getString} from 'core/str';
import DynamicForm from 'core_form/dynamicform';
import {add as addToast} from 'core/toast';
import * as pageSelectors from 'local_iomadcustompage/local/selectors';
import Notification from 'core/notification';


let pageId = 0;

// eslint-disable-next-line no-unused-vars
export const init = (id, contextid) => {

    pageId = id;

  // Lets get the form and add into proper container
    editDetailsCard(pageId);
};


const editDetailsCard = (pageid) => {
    const pendingPromise = new Pending('local_iomadcustompage/details:edit');

    // Load audience form with data for editing, then toggle visible controls in the card.
    const detailsForm = initDetailsCardForm();
    detailsForm.load({'id': pageid, 'needactionbuttons': 1})
        .then(() => {
            return pendingPromise.resolve();
        })
        .catch(Notification.exception);
};

const initDetailsCardForm = () => {
    const detailsFormContainer = document.querySelector(pageSelectors.regions.detailsFormContainer);
    const detailsForm = new DynamicForm(detailsFormContainer, '\\local_iomadcustompage\\form\\page');

    // After submitting the form, update the card instance and description properties.
    detailsForm.addEventListener(detailsForm.events.FORM_SUBMITTED, () => {

        return getString('detailssaved', 'local_iomadcustompage')
            .then(addToast).then(() => {
              return window.location.reload();
            });
    });

    return detailsForm;
};

