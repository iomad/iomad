<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');
require_once('lib.php');

require_commerce_enabled();

class confirmation_form extends moodleform {
    protected $basket = null;
    protected $paymentprovider = null;
    
    function __construct($actionurl, $basket, $paymentprovider) {
        global $CFG;
        
        $this->basket = $basket;
        $this->paymentprovider = $paymentprovider;

        parent::moodleform($actionurl);
    }

    function definition() {
        global $CFG;
    
        $mform =& $this->_form;
        
        $mform->addElement('html', $this->paymentprovider->get_order_review_html());
        $mform->addElement('static', 'firstname', get_string('firstname'));
        $mform->addElement('static', 'lastname',  get_string('lastname'));
        $mform->addElement('static', 'company', get_string('company', 'block_iomad_company_admin'));
        $mform->addElement('static', 'address', get_string('address'));
        $mform->addElement('static', 'city', get_string('city'));
        $mform->addElement('static', 'postcode', get_string('postcode','block_iomad_commerce'));
        $mform->addElement('static', 'country', get_string('country'));
        $mform->addElement('static', 'email', get_string('email'));
        $mform->addElement('static', 'phone1', get_string('phone'));

        $mform->addElement('html', get_basket_html());

        $this->add_action_buttons(true, get_string('confirm'));
    }

}


global $DB;

// Correct the navbar 
// Set the name for the page
$linktext=get_string('course_shop_title', 'block_iomad_commerce');
// set the url
$linkurl = new moodle_url('/blocks/iomad_commerce/shop.php');
//build the nav bar
$PAGE->navbar->add($linktext, $linkurl);
$PAGE->navbar->add(get_string('review', 'block_iomad_commerce'));

$blockpage = new blockpage($PAGE, $OUTPUT, 'iomad_commerce', 'block', 'review');
$blockpage->setup();

require_login(null, false); // Adds to $PAGE, creates $OUTPUT
$context = $PAGE->context;

// don't do the pre_order_review_processing on postback
if (array_key_exists('submitbutton', $_POST)) {
    $basket = get_basket();
    $pp = get_payment_provider_instance($basket->checkout_method);
} else {
	// add the rest of the stuff to the basket invoice
	$basket = get_basket();
	$pp = get_payment_provider_instance($basket->checkout_method);
    $pp->pre_order_review_processing();
    // refresh basket info after processing
    $basket = get_basket();
}

$mform = new confirmation_form($PAGE->url, $basket, $pp);
$mform->set_data($basket);

$error = '';

if ($mform->is_cancelled()) {
    redirect('basket.php');

} else if ($data = $mform->get_data()) {

    $error = $pp->confirm();
    if (!$error) {
        redirect('confirm.php?u=' . $basket->reference);
    }
}

$blockpage->display_header();

echo $error;

$mform->display();

echo $OUTPUT->footer();
