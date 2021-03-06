<?php
/**
 * Wibble
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://github.com/padraic/wibble/blob/master/LICENSE
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to padraic@php.net so we can send you a copy immediately.
 *
 * @category   Mockery
 * @package    Mockery
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2010 Pádraic Brady (http://blog.astrumfutura.com)
 * @license    http://github.com/padraic/wibble/blob/master/LICENSE New BSD License
 */

/**
 * @namespace
 */
namespace WibbleTest;
use Wibble;

class SanitizeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * HTML elements exceptions
     */
    protected $nonclosingTags = array(
        'area', 'br', 'hr', 'img', 'input'
    );
    protected $ignoredTags = array( // again, leaving the SVG stuff until another time
        'animateColor', 'animateMotion', 'animateTransform', 'foreignObject',
        'linearGradient', 'radialGradient', 'title'
    );
    protected $imposedParentTags = array(
        'dd'=>'dl', 'dt'=>'dl', 'li'=>'ul'
    );
    protected $imposedChildTags = array(
        'dl'=>'dt'
    );

    /**
     * Test Helpers
     */
    
    protected function sanitizeHTMLWithTidy($string)
    {
        if (!class_exists('\tidy', false)) $this->markTestSkipped('Tidy unavailable');
        $fragment = new Wibble\HTML\Fragment($string);
        $fragment->filter('escape');
        return $fragment->toString();
    }
    
    protected function sanitizeHTMLWithoutTidy($string)
    {
        $fragment = new Wibble\HTML\Fragment($string, array('disable_tidy'=>true));
        $fragment->filter('escape');
        return $fragment->toString();
    }
    
    protected function checkSanitizationOfNormalTagWithoutTidy($tag)
    {
        $input       = "<{$tag} title=\"1\">foo <bad>bar</bad> baz</{$tag}>";
        $htmlOutput  = "<{$tag} title=\"1\">foo &lt;bad&gt;bar&lt;/bad&gt; baz</{$tag}>";
        if (in_array($tag, $this->nonclosingTags)
        || (in_array($tag, array('col')))) {
            $htmlOutput  = "<{$tag} title=\"1\">foo &lt;bad&gt;bar&lt;/bad&gt; baz";
        } elseif (in_array($tag, $this->ignoredTags)) {
            return;
        }
        $sane = $this->sanitizeHTMLWithoutTidy($input);
        $sane = str_replace("\n", '', $sane);
        $this->assertTrue(($htmlOutput == $sane), $input);
    }
    
    protected function checkSanitizationOfNormalTagWithTidy($tag)
    {
        if (!class_exists('\tidy', false)) $this->markTestSkipped('Tidy unavailable');
        $input       = "<{$tag} title=\"1\">foo <bad>bar</bad> baz</{$tag}>";
        $htmlOutput  = "<{$tag} title=\"1\">foo &lt;bad&gt;bar&lt;/bad&gt; baz</{$tag}>";
        if (in_array($tag, $this->nonclosingTags)
        || (in_array($tag, array('col')) && !class_exists('\\tidy', false))) {
            $htmlOutput  = "<{$tag} title=\"1\">foo &lt;bad&gt;bar&lt;/bad&gt; baz";
        } elseif (in_array($tag, $this->ignoredTags)) {
            return;
        }
        /**
         * Set expected output conditionally where departs from default
         */
        if (class_exists('\\tidy', false)) {
            if (in_array($tag, array('caption'))) {
                $htmlOutput = '<table><'. $tag . ' title="1">foo &lt;bad&gt;bar&lt;/bad&gt; baz</' . $tag . '></table>';
            } elseif (in_array($tag, array('colgroup'))) {
                $htmlOutput = 'foo &lt;bad&gt;bar&lt;/bad&gt; baz<table><'. $tag . ' title="1"></' . $tag . '></table>';
            } elseif (in_array($tag, array('table'))) {
                $htmlOutput = 'foo &lt;bad&gt;bar&lt;/bad&gt; baz<table title="1"></table>';
            } elseif (in_array($tag, array('optgroup', 'option', 'tbody', 'tfoot', 'thead'))) {
                $htmlOutput = 'foo &lt;bad&gt;bar&lt;/bad&gt; baz';
            } elseif ($tag == 'td') {
                $htmlOutput = '<table><tr><td title="1">foo &lt;bad&gt;bar&lt;/bad&gt; baz</td></tr></table>';
            } elseif ($tag == 'th') {
                $htmlOutput = '<table><tr><th title="1">foo &lt;bad&gt;bar&lt;/bad&gt; baz</th></tr></table>';
            } elseif ($tag == 'tr') {
                $htmlOutput = 'foo &lt;bad&gt;bar&lt;/bad&gt; baz<table><tr title="1"><td></td></tr></table>';
            } elseif ($tag == 'col') {
                $htmlOutput = 'foo &lt;bad&gt;bar&lt;/bad&gt; baz<table><col title="1"></table>';
            } elseif ($tag == 'table') {
                $htmlOutput = 'foo &lt;bad&gt;bar&lt;/bad&gt;baz<table title="1"> </table>';
            } elseif ($tag == 'image') {
                $htmlOutput = '<img title="1"/>foo &lt;bad&gt;bar&lt;/bad&gt; baz';
            } elseif ($tag == 'input') {
                $htmlOutput = '<form><input title="1">foo &lt;bad&gt;bar&lt;/bad&gt; baz</form>';
            } elseif (in_array($tag, array('dir', 'menu', 'ol', 'ul'))) {
                $htmlOutput = '<div style="margin-left: 2em" title="1">foo &lt;bad&gt;bar&lt;/bad&gt; baz</div>';
            } elseif (in_array($tag, Wibble\Filter\Whitelist::$voidElements)) {
                $htmlOutput = '<' . $tag . ' title="1">foo &lt;bad&gt;bar&lt;/bad&gt; baz';
            } elseif (isset($this->imposedParentTags[$tag])) {
                $parent = $this->imposedParentTags[$tag];
                $htmlOutput = "<{$parent}><{$tag} title=\"1\">foo &lt;bad&gt;bar&lt;/bad&gt; baz</{$tag}></{$parent}>";
            } elseif (isset($this->imposedChildTags[$tag])) {
                $child = $this->imposedChildTags[$tag];
                $htmlOutput = "<{$tag} title=\"1\"><{$child}>foo &lt;bad&gt;bar&lt;/bad&gt; baz</{$child}></{$tag}>";
            } elseif (in_array($tag, array('select'))) {
                $htmlOutput = '';
            }
        }
        $sane = $this->sanitizeHTMLWithTidy($input);
        $sane = str_replace("\n", '', $sane);
        $this->assertTrue(($htmlOutput == $sane), $input);
    }
    
    protected function checkSanitizationOfNormalAttributeWithTidy($attr)
    {
        if (!class_exists('\tidy', false)) $this->markTestSkipped('Tidy unavailable');
        $input = "<p {$attr}=\"foo\">foo <bad>bar</bad> baz</p>";
        if (in_array($attr, array('checked', 'compact', 'disabled', 'ismap',
        'multiple', 'nohref', 'noshade', 'nowrap', 'readonly', 'selected'))) {
            $htmlOutput = "<p {$attr}>foo &lt;bad&gt;bar&lt;/bad&gt; baz</p>";
        } else {
            $htmlOutput = "<p {$attr}=\"foo\">foo &lt;bad&gt;bar&lt;/bad&gt; baz</p>";
        }
        // "foo" is not valid CSS so should be blank
        if ($attr == 'style') $htmlOutput = "<p {$attr}=\"\">foo &lt;bad&gt;bar&lt;/bad&gt; baz</p>";
        // "xml:lang" becomes lang for HTML
        if ($attr == 'xml:lang') $htmlOutput = "<p lang=\"foo\">foo &lt;bad&gt;bar&lt;/bad&gt; baz</p>";
        $sane = $this->sanitizeHTMLWithTidy($input);
        $sane = str_replace("\n", '', $sane);
        $this->assertTrue(($htmlOutput == $sane), $input);
    }
    
    protected function checkSanitizationOfNormalAttributeWithoutTidy($attr)
    {
        $input = "<p {$attr}=\"foo\">foo <bad>bar</bad> baz</p>";
        if (in_array($attr, array('checked', 'compact', 'disabled', 'ismap',
        'multiple', 'nohref', 'noshade', 'nowrap', 'readonly', 'selected'))) {
            $htmlOutput = "<p {$attr}>foo &lt;bad&gt;bar&lt;/bad&gt; baz</p>";
        } else {
            $htmlOutput = "<p {$attr}=\"foo\">foo &lt;bad&gt;bar&lt;/bad&gt; baz</p>";
        }
        // "foo" is not valid CSS so should be blank
        if ($attr == 'style') $htmlOutput = "<p {$attr}=\"\">foo &lt;bad&gt;bar&lt;/bad&gt; baz</p>";
        $sane = $this->sanitizeHTMLWithoutTidy($input);
        $sane = str_replace("\n", '', $sane);
        $this->assertTrue(($htmlOutput == $sane), $input);
    }
    
    protected function checkSanitizationOfAcceptableProtocolsLowerCasedWithTidy($protocol)
    {
        if (!class_exists('\tidy', false)) $this->markTestSkipped('Tidy unavailable');
        $input = "<a href=\"{$protocol}\">foo</a>";
        $htmlOutput = "<a href=\"{$protocol}\">foo</a>";
        $sane = $this->sanitizeHTMLWithTidy($input);
        $sane = str_replace("\n", '', $sane);
        $this->assertTrue(($htmlOutput == $sane), $input);
    }
    
    protected function checkSanitizationOfAcceptableProtocolsUpperCasedWithTidy($protocol)
    {
        if (!class_exists('\tidy', false)) $this->markTestSkipped('Tidy unavailable');
        $protocol = strtoupper($protocol);
        $input = "<a href=\"{$protocol}\">foo</a>";
        $htmlOutput = "<a href=\"{$protocol}\">foo</a>";
        $sane = $this->sanitizeHTMLWithTidy($input);
        $sane = str_replace("\n", '', $sane);
        $this->assertTrue(($htmlOutput == $sane), $input);
    }
    
    protected function checkSanitizationOfAcceptableProtocolsLowerCasedWithoutTidy($protocol)
    {
        $input = "<a href=\"{$protocol}\">foo</a>";
        $htmlOutput = "<a href=\"{$protocol}\">foo</a>";
        $sane = $this->sanitizeHTMLWithoutTidy($input);
        $sane = str_replace("\n", '', $sane);
        $this->assertTrue(($htmlOutput == $sane), $input);
    }
    
    protected function checkSanitizationOfAcceptableProtocolsUpperCasedWithoutTidy($protocol)
    {
        $protocol = strtoupper($protocol);
        $input = "<a href=\"{$protocol}\">foo</a>";
        $htmlOutput = "<a href=\"{$protocol}\">foo</a>";
        $sane = $this->sanitizeHTMLWithoutTidy($input);
        $sane = str_replace("\n", '', $sane);
        $this->assertTrue(($htmlOutput == $sane), $input);
    }

    /**
     * Tests
     *
     * Tests are aggregated simply because if one fails, it's easy to spot. Also
     * I am not writing 500+ tests individually ;)
     */
    
    /**
     * @group sanitise_escape
     */
    public function testAllowsAcceptableElements()
    {
        $acceptableTags = array_merge(
            Wibble\Filter\Whitelist::$acceptableElements
        );
        $acceptableXmlTags = array_merge(
            Wibble\Filter\Whitelist::$mathmlElements,
            Wibble\Filter\Whitelist::$svgElements
        );
        foreach ($acceptableTags as $tag) {
            $this->checkSanitizationOfNormalTagWithTidy($tag);
        }
        foreach ($acceptableTags as $tag) {
            $this->checkSanitizationOfNormalTagWithoutTidy($tag);
        }
        foreach ($acceptableXmlTags as $tag) {
            $this->checkSanitizationOfNormalTagWithoutTidy($tag);
        }
        // todo - XML under tidy
    }
    
    public function testAllowsAcceptableAttributes()
    {
        foreach (Wibble\Filter\Whitelist::$acceptableAttributes as $attr) {
            $this->checkSanitizationOfNormalAttributeWithTidy($attr);
        }
        foreach (Wibble\Filter\Whitelist::$acceptableAttributes as $attr) {
            $this->checkSanitizationOfNormalAttributeWithoutTidy($attr);
        }
    }
    
    public function testAllowsAcceptableProtocols()
    {
        foreach (Wibble\Filter\Whitelist::$acceptableProtocols as $prot) {
            $this->checkSanitizationOfAcceptableProtocolsLowerCasedWithTidy($prot);
        }
        foreach (Wibble\Filter\Whitelist::$acceptableProtocols as $prot) {
            $this->checkSanitizationOfAcceptableProtocolsUpperCasedWithTidy($prot);
        }
        foreach (Wibble\Filter\Whitelist::$acceptableProtocols as $prot) {
            $this->checkSanitizationOfAcceptableProtocolsLowerCasedWithoutTidy($prot);
        }
        foreach (Wibble\Filter\Whitelist::$acceptableProtocols as $prot) {
            $this->checkSanitizationOfAcceptableProtocolsUpperCasedWithoutTidy($prot);
        }
    }
    
    /**
     * TODO: SVG stuff later
     */

}
