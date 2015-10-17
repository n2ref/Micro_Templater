<?php

require_once __DIR__ . '/../Mtpl.php';


/**
 * Class MtplTest
 */
class MtplTest extends PHPUnit_Framework_TestCase {


    /**
     * @param string $filename
     * @dataProvider providerConstructor
     */
    public function testConstructor($filename) {
        $tpl = new Mtpl($filename);

        if ( ! ($tpl instanceof Mtpl)) {
            $this->fail('Error in constructor');
        }
    }


    /**
     * @return array
     */
    public function providerConstructor() {
        return array(
            array(null),
            array(''),
            array(__DIR__ . '/templates/tpl1.html'),
            array(__DIR__ . '/templates/tpl2.html'),
            array(__DIR__ . '/templates/tpl3.html'),
        );
    }


    /**
     * @param string $filename
     * @dataProvider providerErrorConstructor
     * @expectedException Exception
     */
    public function testErrorConstructor($filename) {
        new Mtpl($filename);
    }


    /**
     * @return array
     */
    public function providerErrorConstructor() {
        return array(
            array('123'),
            array(__DIR__ . '/templates/tpl'),
            array(1111)
        );
    }


    /**
     * @dataProvider providerLoadTemplate
     * @param $filename
     */
    public function testLoadTemplate($filename) {
        $tpl = new Mtpl();
        $tpl->loadTemplate($filename);
    }


    /**
     * @return array
     */
    public function providerLoadTemplate() {
        return array(
            array(__DIR__ . '/templates/tpl1.html'),
            array(__DIR__ . '/templates/tpl2.html'),
            array(__DIR__ . '/templates/tpl3.html'),
        );
    }


    /**
     * @param string $filename
     * @dataProvider providerErrorLoadTemplate
     * @expectedException Exception
     */
    public function testErrorLoadTemplate($filename) {
        $tpl = new Mtpl();
        $tpl->loadTemplate($filename);
    }


    /**
     * @return array
     */
    public function providerErrorLoadTemplate() {
        return array(
            array(null),
            array(''),
            array('123'),
            array(__DIR__ . '/templates/tpl'),
            array(1111)
        );
    }


    /**
     * @dataProvider providerSetTemplate
     * @param string $template
     */
    public function testSetTemplate($template) {
        $tpl = new Mtpl();
        $tpl->setTemplate($template);
    }


    /**
     * @return array
     */
    public function providerSetTemplate() {
        return array(
            array(file_get_contents(__DIR__ . '/templates/tpl1.html')),
            array(file_get_contents(__DIR__ . '/templates/tpl2.html')),
            array(file_get_contents(__DIR__ . '/templates/tpl3.html')),
        );
    }


    /**
     * @dataProvider providerAssign
     * @param string $var
     * @param string $value
     */
    public function testAssign($var, $value) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');
        $tpl->assign($var, $value);
    }


    /**
     * @return array
     */
    public function providerAssign() {
        return array(
            array('[TITLE]', 'title_value'),
            array('[TITLE]', ''),
            array('', ''),
            array('', 'qwerty'),
            array(123, 456),
            array(678, '890'),
        );
    }


    /**
     * @dataProvider provider__get
     * @param string $block
     */
    public function test__get($block) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');
        $tpl_block = $tpl->$block;

        if ( ! ($tpl_block instanceof Mtpl)) {
            $this->fail("Correct block '{$block}' not found");
        }
    }


    /**
     * @return array
     */
    public function provider__get() {
        return array(
            array('js'),
            array('css'),
            array('menu'),
            array('submenu'),
            array('sub_element'),
        );
    }


    /**
     * @dataProvider providerError__get
     * @param string $block
     * @expectedException Exception
     */
    public function testError__get($block) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');
        $tpl->$block;
    }


    /**
     * @return array
     */
    public function providerError__get() {
        return array(
            array(null),
            array(false),
            array(''),
            array('123'),
            array(123),
            array('1test'),
            array('block not found'),
        );
    }


    /**
     * @dataProvider provider__isset
     * @param string $block
     */
    public function test__isset($block) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');
        if ( ! isset($tpl->$block)) {
            $this->fail("Correct block '{$block}' not found");
        }
    }


    /**
     * @return array
     */
    public function provider__isset() {
        return array(
            array('css'),
            array('js'),
            array('menu')
        );
    }


    /**
     * @dataProvider providerError__isset
     * @param string $block
     * @expectedException Exception
     * @throws Exception
     */
    public function testError__isset($block) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');

        if (isset($tpl->$block)) {
            $this->fail("Found incorrect block '{$block}'");
        } else {
            throw new Exception('ok');
        }
    }


    /**
     * @return array
     */
    public function providerError__isset() {
        return array(
            array(null),
            array(false),
            array(''),
            array('123'),
            array(123),
            array('test'),
            array('block not found'),
        );
    }


    /**
     *
     */
    public function testReassign() {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');
        $tpl->reassign();
    }


    /**
     * @dataProvider providerTouchBlock
     */
    public function testTouchBlock($block) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');
        $tpl->touchBlock($block);
    }


    /**
     * @return array
     */
    public function providerTouchBlock() {
        return array(
            array('css'),
            array('js'),
            array('menu')
        );
    }


    /**
     * @dataProvider providerGetBlock
     * @param string $block
     */
    public function testGetBlock($block) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');
        $html = $tpl->getBlock($block);
        if ( ! is_string($html)) {
            $this->fail("Block '{$block}' empty");
        }
    }


    /**
     * @return array
     */
    public function providerGetBlock() {
        return array(
            array('css'),
            array('js'),
            array('menu'),
            array('empty'),
        );
    }


    /**
     * @dataProvider providerErrorGetBlock
     * @param string $block
     * @expectedException Exception
     */
    public function testErrorGetBlock($block) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');
        $tpl->getBlock($block);
    }


    /**
     * @return array
     */
    public function providerErrorGetBlock() {
        return array(
            array('123'),
            array(123),
            array('test'),
            array('block not found'),
        );
    }


    /**
     * @dataProvider providerFillDropDown
     * @param string       $selector
     * @param array        $options
     * @param string|array $selected
     * @throws Exception
     */
    public function testFillDropDown($selector, $options, $selected) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl2.html');
        $tpl->fillDropDown($selector, $options, $selected);
    }


    /**
     * @return array
     */
    public function providerFillDropDown() {
        return array(
            array(
                'select',
                array(),
                null
            ),
            array(
                'select',
                array('a','b','c','d'),
                '0'
            ),
            array(
                'method',
                array('a','b','c','d'),
                '2'
            ),
            array(
                'select.sel',
                array('a','b','c','d'),
                '3'
            ),
            array(
                'select.sel#method',
                array('a','b','c','d'),
                '4'
            ),
            array(
                'select#method.sel',
                array('a','b','c','d'),
                '1'
            ),
            array(
                '.sel',
                array('a','b','c','d'),
                array(0, 3)
            ),
            array(
                '#method',
                array('a','b','c','d'),
                array(1, 2)
            ),
            array(
                'select',
                array(
                    'A' => array(1 => 'a', 2 => 'b', 3 => 'c', 4 => 'd'),
                    'B' => array(5 => 'a2', 6 => 'b2', 7 => 'c2', 8 => 'd2'),
                    'C' => array(9 => 'a3', 10 => 'b3', 11 => 'c3', 12 => 'd3'),
                ),
                3
            ),
            array(
                'select',
                array(
                    'A' => array(1 => 'a', 2 => 'b', 3 => 'c', 4 => 'd'),
                    'B' => array(5 => 'a2', 6 => 'b2', 7 => 'c2', 8 => 'd2'),
                    'C' => array(9 => 'a3', 10 => 'b3', 11 => 'c3', 12 => 'd3'),
                ),
                array('4', '6', 12)
            )
        );
    }


    /**
     * @dataProvider providerRender
     * @param string $filename
     */
    public function testRender($filename) {
        $tpl = new Mtpl($filename);
        $result = $tpl->render();
        if ( ! is_string($result)) {
            $this->fail("Method 'render' return not string");
        }
    }


    /**
     * @return array
     */
    public function providerRender() {
        return array(
            array(__DIR__ . '/templates/tpl1.html'),
            array(__DIR__ . '/templates/tpl2.html'),
            array(__DIR__ . '/templates/tpl3.html'),
        );
    }


    /**
     * @dataProvider providerComplecs
     * @param string $menu
     */
    public function testComplecs($menu) {
        $tpl = new Mtpl(__DIR__ . '/templates/tpl1.html');

        $tpl->assign('[TITLE]', 'title');

        $tpl->touchBlock('js');
        $tpl->touchBlock('css');

        $tpl->js->assign('[SRC]',   '/js/script.js');
        $tpl->css->assign('[HREF]', '/css/style.css');


        foreach ($menu as $element) {
            $tpl->menu->assign('[NAME]',   $element['name']);
            $tpl->menu->assign('[TITLE]',  $element['title']);

            $tpl->menu->reassign();
        }

        $tp_content = new Mtpl(__DIR__ . '/templates/tpl2.html');
        $tp_content->fillDropDown(
            'sel',
            array(
                '25'  => '25',
                '50'  => '50',
                '100' => '100',
                '0'   => 'all'
            ),
            25
        );
        $tpl->assign('[CONTENT]', $tp_content->render());

        $result = $tpl->render();

        if ( ! is_string($result) || empty($result)) {
            $this->fail("Method 'render' return not string");
        }
    }


    /**
     * @return array
     */
    public function providerComplecs() {
        return array(
            array(array('name' => '123', 'title' => 'qwe')),
            array(array('name' => '456', 'title' => 'asd', 'active' => true)),
            array(array('name' => '789', 'title' => 'zxc')),
        );
    }
}
