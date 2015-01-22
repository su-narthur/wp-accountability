<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HfLoginForm extends HfForm {
    private $cms;

    public function __construct(
        $actionUrl,
        Hf_iMarkupGenerator $markupGenerator,
        Hf_iCms $cms,
        Hf_iAssetLocator $assetLocator
    ) {
        $this->elements = array();
        $this->elements[] = '<form action="'.$actionUrl.'" method="post">';

        $this->markupGenerator = $markupGenerator;
        $this->cms = $cms;
        $this->assetLocator = $assetLocator;
    }

    public function getOutput() {
        $this->makeLoginFailureError();
        $this->makeForm();
        $this->validateForm();
        $html = $this->getElementsAsString();
        return $html;
    }

    private function makeForm()
    {
        $this->addUsernameField();
        $this->addPasswordBox('password', 'Password', true);
        $this->addSubmitButton('login', 'Log In');
    }

    private function addUsernameField()
    {
        $username = (isset($_POST['username']) ? $_POST['username'] : '');
        $this->addTextBox('username', 'Username', $username, true);
    }

    private function getElementsAsString()
    {
        $html = '';
        foreach ($this->elements as $element) {
            $html .= $element;
        }
        $html .= '</form>';
        return $html;
    }

    private function validateForm()
    {
        $this->validateUsername();
        $this->validatePassword();
    }

    private function validateUsername()
    {
        if (empty($_POST['username'])) {
            $error = $this->markupGenerator->makeErrorMessage('Please enter your username.');
            $this->enqueError($error);
        }
    }

    private function validatePassword()
    {
        if (empty($_POST['password'])) {
            $error = $this->markupGenerator->makeErrorMessage('Please enter your password.');
            $this->enqueError($error);
        }
    }

    public function attemptLogin()
    {
        if ($this->isLoggingIn()) {
            $userOrError = $this->cms->authenticateUser($_POST['username'], $_POST['password']);

            if (!$this->cms->isError($userOrError)) {
                $this->redirectUser();
            }
        }
    }

    private function redirectUser()
    {
        $homeUrl = $this->assetLocator->getHomePageUrl();
        print $this->markupGenerator->makeRedirectScript($homeUrl);
    }

    private function isLoggingIn()
    {
        return isset($_POST['username']) && isset($_POST['password']);
    }

    private function makeLoginFailureError()
    {
        if ($this->isLoggingIn()) {
            $error = $this->markupGenerator->makeErrorMessage('That username and password combination is incorrect.');
            $this->enqueError($error);
        }
    }

    private function enqueError($error)
    {
        array_unshift($this->elements, $error);
    }
} 