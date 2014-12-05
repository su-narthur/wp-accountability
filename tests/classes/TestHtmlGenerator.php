<?php
if ( ! defined( 'ABSPATH' ) ) exit;
require_once( dirname( dirname( __FILE__ ) ) . '/HfTestCase.php' );

class TestHtmlGenerator extends HfTestCase {
    public function testCreateEmptyList() {
        $list = $this->Factory->makeMarkupGenerator()->makeList(array());

        $this->assertEquals('<ul></ul>', $list);
    }

    public function testCreateListWithOneItem() {
        $list = $this->Factory->makeMarkupGenerator()->makeList(array('item'));
        $expected = '<ul><li>item</li></ul>';

        $this->assertEquals($expected, $list);
    }

    public function testCreateListWithTwoItems() {
        $list = $this->Factory->makeMarkupGenerator()->makeList(array('item 1', 'item 2', 'item 3'));
        $expected = '<ul><li>item 1</li><li>item 2</li><li>item 3</li></ul>';

        $this->assertEquals($expected, $list);
    }

    public function testHtmlGeneratorCreatesTabs() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();

        $contents = array(
            'duck1' => 'quack',
            'duck2' => 'quack, quack',
            'duck3' => 'quack, quack, quack'
        );

        $expected = '[su_tabs active="1"][su_tab title="duck1"]quack[/su_tab][su_tab title="duck2"]quack, quack[/su_tab][su_tab title="duck3"]quack, quack, quack[/su_tab][/su_tabs]';

        $result = $HtmlGenerator->generateTabs( $contents, 1 );

        $this->assertTrue( strstr( $result, $expected ) != false );
    }

    public function testHtmlGeneratorCreatesDifferentTabs() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();

        $contents = array(
            'duck1' => 'quack',
            'duck2' => 'quack, quack'
        );

        $expected = '[su_tabs active="2"][su_tab title="duck1"]quack[/su_tab][su_tab title="duck2"]quack, quack[/su_tab][/su_tabs]';

        $result = $HtmlGenerator->generateTabs( $contents, 2 );

        $isStringThere = ( strstr( $result, $expected ) != false );
        $this->assertTrue( $isStringThere );
    }

    public function testMakeError() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();
        $result = $HtmlGenerator->makeErrorMessage('duck');
        $this->assertEquals('<p class="error">duck</p>', $result);
    }

    public function testMakeSuccessMessage() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();
        $result = $HtmlGenerator->makeSuccessMessage('duck');
        $this->assertEquals('<p class="success">duck</p>', $result);
    }

    public function testMakeQuoteMessage() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();

        $MockQuotation = new stdClass();
        $MockQuotation->post_content = 'hello';
        $MockQuotation->post_title = 'Nathan';

        $result = $HtmlGenerator->makeQuoteMessage($MockQuotation);

        $this->assertEquals('<p class="quote">"hello" — Nathan</p>', $result);
    }

    public function testMakeForm() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();
        $expected = '<form action="pond.net" method="post" name="waterform">duck</form>';
        $actual = $HtmlGenerator->makeForm('pond.net', 'duck', 'waterform');
        $this->assertEquals($expected, $actual);
    }

    public function testMakeButton() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();
        $name = 'DUCK';
        $label = 'duck';
        $onclick = 'quack';
        $expected = '<input type="button" name="'.$name.'" value="'.$label.'" onclick="'.$onclick.'" />';
        $actual = $HtmlGenerator->makeButtonInput($name, $label, $onclick);
        $this->assertEquals($expected, $actual);
    }

    public function testMakeHiddenField() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();
        $actual = $HtmlGenerator->makeHiddenField('ghost');
        $expected = '<input type="hidden" name="ghost" />';
        $this->assertEquals($expected, $actual);
    }

    public function testMakeInfoMessage() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();
        $result = $HtmlGenerator->makeInfoMessage('duck');
        $this->assertEquals('<p class="info">duck</p>', $result);
    }

    public function testMakeRedirectScript() {
        $HtmlGenerator = $this->Factory->makeMarkupGenerator();
        $result = $HtmlGenerator->makeRedirectScript('duck');
        $this->assertEquals('<script>window.location.replace("duck");</script>', $result);
    }

    public function testMakeRefreshScript() {
        $generator = $this->Factory->makeMarkupGenerator();
        $result = $generator->makeRefreshScript();
        $this->assertEquals('<script>window.location.reload();</script>', $result);
    }

    public function testMakeGoalCard() {
        $goalTitle = 'Title';
        $goalDescription = 'Description';
        $goalId = 1;
        $levelId = 2;
        $levelTitle = 'Title';
        $levelPercent = 0;
        $levelDaysToComplete = 14;
        $levelBar = '';

        $result = $this->MarkupGeneratorWithMockedDependencies->makeGoalCard(
            $goalTitle,
            $goalDescription,
            $goalId,
            $levelId,
            $levelTitle,
            $levelPercent,
            $levelDaysToComplete,
            $levelBar
        );

        $expected = "<div class='report-card'>" .
            "<div class='main'><div class='about'><h2>Title</h2><p>Description</p></div>" .
            "<div class='report'>Have you fallen since your last check-in?<div class='controls'>" .
            "<label class='success'><input type='radio' name='1' value='1'> No</label>" .
            "<label class='setback'><input type='radio' name='1' value='0'> Yes</label>" .
            "</div></div></div>" .
            "<div class='stats'>" .
            "<p class='stat'>Level <span class='number'>2</span> Title</p>" .
            "<p class='stat'>Level <span class='number'>0%</span> Complete</p>" .
            "<p class='stat'>Days to <span class='number'>14</span> Next Level</p>" .
            "</div></div>";

        $this->assertEquals($expected, $result);
    }

    public function testMakeGoalCardDoesntIncludeEmptyDescriptionParagraph() {
        $goalTitle = 'Title';
        $goalDescription = '';
        $goalId = 1;
        $levelId = 2;
        $levelTitle = 'Title';
        $levelPercent = 0;
        $levelDaysToComplete = 14;
        $levelBar = '';

        $result = $this->MarkupGeneratorWithMockedDependencies->makeGoalCard(
            $goalTitle,
            $goalDescription,
            $goalId,
            $levelId,
            $levelTitle,
            $levelPercent,
            $levelDaysToComplete,
            $levelBar
        );

        $expected = "<div class='report-card'>" .
            "<div class='main'><div class='about'><h2>Title</h2></div>" .
            "<div class='report'>Have you fallen since your last check-in?<div class='controls'>" .
            "<label class='success'><input type='radio' name='1' value='1'> No</label>" .
            "<label class='setback'><input type='radio' name='1' value='0'> Yes</label>" .
            "</div></div></div>" .
            "<div class='stats'>" .
            "<p class='stat'>Level <span class='number'>2</span> Title</p>" .
            "<p class='stat'>Level <span class='number'>0%</span> Complete</p>" .
            "<p class='stat'>Days to <span class='number'>14</span> Next Level</p>" .
            "</div></div>";

        $this->assertEquals($result, $expected);
    }
}
