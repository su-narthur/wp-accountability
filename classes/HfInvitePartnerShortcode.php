<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class HfInvitePartnerShortcode implements Hf_iShortcode {
    private $AssetLocator;
    private $MarkupGenerator;
    private $UserManager;

    private $messages;

    function __construct( Hf_iAssetLocator $AssetLocator, Hf_iMarkupGenerator $MarkupGenerator, Hf_iUserManager $UserManager ) {
        $this->AssetLocator    = $AssetLocator;
        $this->MarkupGenerator = $MarkupGenerator;
        $this->UserManager     = $UserManager;
    }

    public function getOutput() {

        $this->processForm();

        $header = $this->MarkupGenerator->head('Invite Partner', 2);
        $form   = $this->generateForm();

        return $header . $this->messages . $form->getOutput();
    }

    private function processForm() {
        if ( $this->isFormSubmitted() ) {
            $this->validateForm();
            $this->sendInvitation();
        }
    }

    private function generateForm() {
        $currentUrl = $this->AssetLocator->getCurrentPageUrl();
        $form       = new HfGenericForm( $currentUrl, $this->MarkupGenerator );

        $form->addInfoMessage( '<strong>Note:</strong> By inviting someone to become a partner you grant them access to all your goals and progress history.' );
        $form->addTextBox( 'email', 'Email', '', true );
        $form->addSubmitButton( 'submit', 'Invite' );

        return $form;
    }

    private function isFormSubmitted() {
        return isset( $_POST['submit'] );
    }

    private function validateForm() {
        if ( $this->isFormSubmitted() and $this->isEmailInvalid() ) {
            $this->messages .= $this->MarkupGenerator->errorMessage( 'Please enter a valid email address.' );
        }
    }

    private function sendInvitation() {
        if ( empty( $this->messages ) ) {
            $inviterId = $this->UserManager->getCurrentUserId();
            $this->UserManager->sendInvitation( $inviterId, $_POST['email'] );
            $this->messages .= '<p class="success">' . $_POST['email'] . ' has been successfully invited to partner with you.</p>';
        }
    }

    private function isEmailInvalid() {
        return !filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL );
    }
}