<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Form\View\Helper;

use Zend\Form\Element;
use Zend\Form\Element\Select as SelectElement;
use Zend\Form\View\Helper\FormSelect as FormSelectHelper;

class FormSelectTest extends CommonTestCase
{
    public function setUp()
    {
        $this->helper = new FormSelectHelper();
        parent::setUp();
    }

    public function getElement()
    {
        $element = new SelectElement('foo');
        $options = array(
            array(
                'label' => 'This is the first label',
                'value' => 'value1',
            ),
            array(
                'label' => 'This is the second label',
                'value' => 'value2',
            ),
            array(
                'label' => 'This is the third label',
                'value' => 'value3',
                'attributes' => array(
                    'class' => 'test-class',
                ),
            ),
        );
        $element->setValueOptions($options);
        return $element;
    }

    public function testCreatesSelectWithOptionsFromAttribute()
    {
        $element = $this->getElement();
        $markup  = $this->helper->render($element);

        $this->assertEquals(1, substr_count($markup, '<select'));
        $this->assertEquals(1, substr_count($markup, '</select>'));
        $this->assertEquals(3, substr_count($markup, '<option'));
        $this->assertEquals(3, substr_count($markup, '</option>'));
        $this->assertContains('>This is the first label<', $markup);
        $this->assertContains('>This is the second label<', $markup);
        $this->assertContains('>This is the third label<', $markup);
        $this->assertContains('value="value1"', $markup);
        $this->assertContains('value="value2"', $markup);
        $this->assertContains('value="value3"', $markup);

        //Test class attribute on third option
        $this->assertRegexp('#option .*?value="value3" class="test-class"#', $markup);
    }

    public function testCanMarkSingleOptionAsSelected()
    {
        $element = $this->getElement();
        $element->setAttribute('value', 'value2');

        $markup  = $this->helper->render($element);
        $this->assertRegexp('#option .*?value="value2" selected="selected"#', $markup);
        $this->assertNotRegexp('#option .*?value="value1" selected="selected"#', $markup);
        $this->assertNotRegexp('#option .*?value="value3" selected="selected"#', $markup);
    }

    public function testCanOnlyMarkSingleOptionAsSelectedIfMultipleAttributeIsDisabled()
    {
        $element = $this->getElement();
        $element->setAttribute('value', array('value1', 'value2'));

        $this->setExpectedException('Zend\Form\Exception\ExceptionInterface', 'multiple');
        $markup = $this->helper->render($element);
    }

    public function testCanMarkManyOptionsAsSelectedIfMultipleAttributeIsEnabled()
    {
        $element = $this->getElement();
        $element->setAttribute('multiple', true);
        $element->setAttribute('value', array('value1', 'value2'));
        $markup = $this->helper->render($element);

        $this->assertRegexp('#select .*?multiple="multiple"#', $markup);
        $this->assertRegexp('#option .*?value="value1" selected="selected"#', $markup);
        $this->assertRegexp('#option .*?value="value2" selected="selected"#', $markup);
        $this->assertNotRegexp('#option .*?value="value3" selected="selected"#', $markup);
    }

    public function testCanMarkOptionsAsDisabled()
    {
        $element = $this->getElement();
        $options = $element->getValueOptions('options');
        $options[1]['disabled'] = true;
        $element->setValueOptions($options);

        $markup = $this->helper->render($element);
        $this->assertRegexp('#option .*?value="value2" .*?disabled="disabled"#', $markup);
    }

    public function testCanMarkOptionsAsSelected()
    {
        $element = $this->getElement();
        $options = $element->getValueOptions('options');
        $options[1]['selected'] = true;
        $element->setValueOptions($options);

        $markup = $this->helper->render($element);
        $this->assertRegexp('#option .*?value="value2" .*?selected="selected"#', $markup);
    }

    public function testOptgroupsAreCreatedWhenAnOptionHasAnOptionsKey()
    {
        $element = $this->getElement();
        $options = $element->getValueOptions('options');
        $options[1]['options'] = array(
            array(
                'label' => 'foo',
                'value' => 'bar',
            )
        );
        $element->setValueOptions($options);

        $markup = $this->helper->render($element);
        $this->assertRegexp('#optgroup[^>]*?label="This\&\#x20\;is\&\#x20\;the\&\#x20\;second\&\#x20\;label"[^>]*>\s*<option[^>]*?value="bar"[^>]*?>foo.*?</optgroup>#s', $markup);
    }

    public function testCanDisableAnOptgroup()
    {
        $element = $this->getElement();
        $options = $element->getValueOptions('options');
        $options[1]['disabled'] = true;
        $options[1]['options']  = array(
            array(
                'label' => 'foo',
                'value' => 'bar',
            )
        );
        $element->setValueOptions($options);

        $markup = $this->helper->render($element);
        $this->assertRegexp('#optgroup .*?label="This\&\#x20\;is\&\#x20\;the\&\#x20\;second\&\#x20\;label"[^>]*?disabled="disabled"[^>]*?>\s*<option[^>]*?value="bar"[^>]*?>foo.*?</optgroup>#', $markup);
    }

    /**
     * @group ZF2-290
     */
    public function testFalseDisabledValueWillNotRenderOptionsWithDisabledAttribute()
    {
        $element = $this->getElement();
        $element->setAttribute('disabled', false);
        $markup = $this->helper->render($element);

        $this->assertNotContains('disabled', $markup);
    }

    /**
     * @group ZF2-290
     */
    public function testOmittingDisabledValueWillNotRenderOptionsWithDisabledAttribute()
    {
        $element = $this->getElement();
        $element->setAttribute('type', 'select');
        $markup = $this->helper->render($element);

        $this->assertNotContains('disabled', $markup);
    }

    public function testNameShouldHaveArrayNotationWhenMultipleIsSpecified()
    {
        $element = $this->getElement();
        $element->setAttribute('multiple', true);
        $element->setAttribute('value', array('value1', 'value2'));
        $markup = $this->helper->render($element);
        $this->assertRegexp('#<select[^>]*?(name="foo\&\#x5B\;\&\#x5D\;")#', $markup);
    }

    public function getScalarOptionsDataProvider()
    {
        return array(
            array(array('value' => 'string')),
            array(array(1       => 'int')),
            array(array(-1      => 'int-neg')),
            array(array(0x1A    => 'hex')),
            array(array(0123    => 'oct')),
            array(array(2.1     => 'float')),
            array(array(1.2e3   => 'float-e')),
            array(array(7E-10   => 'float-E')),
            array(array(true    => 'bool-t')),
            array(array(false   => 'bool-f')),
        );
    }

    /**
     * @group ZF2-338
     * @dataProvider getScalarOptionsDataProvider
     */
    public function testScalarOptionValues($options)
    {
        $element = new SelectElement('foo');
        $element->setValueOptions($options);
        $markup = $this->helper->render($element);
        list($value, $label) = each($options);
        $this->assertRegexp(sprintf('#option .*?value="%s"#', (string) $value), $markup);
    }

    public function testInvokeWithNoElementChainsHelper()
    {
        $element = $this->getElement();
        $this->assertSame($this->helper, $this->helper->__invoke());
    }

    public function testCanTranslateContent()
    {
        $element = new SelectElement('foo');
        $element->setValueOptions(array(
            array(
                'label' => 'label1',
                'value' => 'value1',
            ),
        ));
        $markup = $this->helper->render($element);

        $mockTranslator = $this->getMock('Zend\I18n\Translator\Translator');
        $mockTranslator->expects($this->exactly(1))
        ->method('translate')
        ->will($this->returnValue('translated content'));

        $this->helper->setTranslator($mockTranslator);
        $this->assertTrue($this->helper->hasTranslator());

        $markup = $this->helper->__invoke($element);
        $this->assertContains('>translated content<', $markup);
    }

    public function testCanTranslateOptGroupLabel()
    {
        $element = new SelectElement('test');
        $element->setValueOptions(array(
            'optgroup' => array(
                'label' => 'translate me',
                'options' => array(
                    '0' => 'foo',
                    '1' => 'bar',
                ),
            ),
        ));

        $mockTranslator = $this->getMock('Zend\I18n\Translator\Translator');
        $mockTranslator->expects($this->at(0))
                       ->method('translate')
                       ->with('translate me')
                       ->will($this->returnValue('translated label'));
        $mockTranslator->expects($this->at(1))
                       ->method('translate')
                       ->with('foo')
                       ->will($this->returnValue('translated foo'));
        $mockTranslator->expects($this->at(2))
                       ->method('translate')
                       ->with('bar')
                       ->will($this->returnValue('translated bar'));

        $this->helper->setTranslator($mockTranslator);
        $this->assertTrue($this->helper->hasTranslator());

        $markup = $this->helper->__invoke($element);

        $this->assertContains('label="translated&#x20;label"', $markup);
        $this->assertContains('>translated foo<', $markup);
        $this->assertContains('>translated bar<', $markup);
    }

    public function testTranslatorMethods()
    {
        $translatorMock = $this->getMock('Zend\I18n\Translator\Translator');
        $this->helper->setTranslator($translatorMock, 'foo');

        $this->assertEquals($translatorMock, $this->helper->getTranslator());
        $this->assertEquals('foo', $this->helper->getTranslatorTextDomain());
        $this->assertTrue($this->helper->hasTranslator());
        $this->assertTrue($this->helper->isTranslatorEnabled());

        $this->helper->setTranslatorEnabled(false);
        $this->assertFalse($this->helper->isTranslatorEnabled());
    }

    public function testDoesNotThrowExceptionIfNameIsZero()
    {
        $element = $this->getElement();
        $element->setName(0);

        $this->helper->__invoke($element);
        $markup = $this->helper->__invoke($element);
        $this->assertContains('name="0"', $markup);
    }

    public function testCanCreateEmptyOption()
    {
        $element = new SelectElement('foo');
        $element->setEmptyOption('empty');
        $element->setValueOptions(array(
            array(
                'label' => 'label1',
                'value' => 'value1',
            ),
        ));
        $markup = $this->helper->render($element);

        $this->assertContains('<option value="">empty</option>', $markup);
    }

    public function testCanCreateEmptyOptionWithEmptyString()
    {
        $element = new SelectElement('foo');
        $element->setEmptyOption('');
        $element->setValueOptions(array(
            array(
                'label' => 'label1',
                'value' => 'value1',
            ),
        ));
        $markup = $this->helper->render($element);

        $this->assertContains('<option value=""></option>', $markup);
    }

    public function testDoesNotRenderEmptyOptionByDefault()
    {
        $element = new SelectElement('foo');
        $element->setValueOptions(array(
            array(
                'label' => 'label1',
                'value' => 'value1',
            ),
        ));
        $markup = $this->helper->render($element);

        $this->assertNotContains('<option value=""></option>', $markup);
    }

    public function testNullEmptyOptionDoesNotRenderEmptyOption()
    {
        $element = new SelectElement('foo');
        $element->setEmptyOption(null);
        $element->setValueOptions(array(
            array(
                'label' => 'label1',
                'value' => 'value1',
            ),
        ));
        $markup = $this->helper->render($element);

        $this->assertNotContains('<option value=""></option>', $markup);
    }

    public function testCanMarkOptionsAsSelectedWhenEmptyOptionOrZeroValueSelected()
    {
        $element = new SelectElement('foo');
        $element->setEmptyOption('empty');
        $element->setValueOptions(array(
            0 => 'label0',
            1 => 'label1',
        ));

        $element->setValue('');
        $markup = $this->helper->render($element);
        $this->assertContains('<option value="" selected="selected">empty</option>', $markup);
        $this->assertContains('<option value="0">label0</option>', $markup);

        $element->setValue('0');
        $markup = $this->helper->render($element);
        $this->assertContains('<option value="">empty</option>', $markup);
        $this->assertContains('<option value="0" selected="selected">label0</option>', $markup);
    }

    public function testHiddenElementWhenAttributeMultipleIsSet()
    {
        $element = new SelectElement('foo');
        $element->setUseHiddenElement(true);
        $element->setUnselectedValue('empty');

        $markup = $this->helper->render($element);
        $this->assertContains('<input type="hidden" name="foo" value="empty"><select', $markup);
    }

    public function testRenderInputNotSelectElementRaisesException()
    {
        $element = new Element\Text('foo');
        $this->setExpectedException('Zend\Form\Exception\InvalidArgumentException');
        $this->helper->render($element);
    }

    public function testRenderElementWithNoNameRaisesException()
    {
        $element = new SelectElement();

        $this->setExpectedException('Zend\Form\Exception\DomainException');
        $this->helper->render($element);
    }
}
