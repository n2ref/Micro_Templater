<?php

/**
 * Class Micro_Templater
 * @see https://github.com/shinji00/Micro_Templater
 */
class Micro_Templater {

    protected $blocks   = array();
    protected $vars     = array();
    protected $_p       = array();
    protected $reassign = false;
    protected $loop     = '';
    protected $html     = '';


    /**
     * @param  string    $template_file
     * @throws Exception
     */
    public function __construct($template_file = '') {
        if ($template_file) $this->loadTemplate($template_file);
    }


    /**
     * Isset block
     * @param  string $block
     * @return bool
     * @throws Exception
     */
    public function __isset($block) {
        $this->checkBlockName($block);
        $begin_pos = strpos($this->html, "<!-- BEGIN {$block} -->");
        $end_pos   = strrpos($this->html, "<!-- END {$block} -->");

        return $begin_pos !== false && $end_pos !== false && $end_pos >= $begin_pos;
    }


    /**
     * Nested blocks will be stored inside $_p
     * @param  string               $block
     * @return Micro_Templater|null
     * @throws Exception
     */
    public function __get($block) {

        if ($this->reassign) $this->startReassign();
        $this->touchBlock($block);

        if ( ! array_key_exists($block, $this->_p)) {
            $tpl = new Micro_Templater();
            $tpl->setTemplate($this->getBlock($block));
            $this->_p[$block] = $tpl;
        }

        return $this->_p[$block];
    }


    /**
     * Load the HTML file to parse
     * @param  string     $filename
     * @throws Exception
     */
    public function loadTemplate($filename) {
        if ( ! file_exists($filename)) {
            throw new Exception("File not found '{$filename}'");
        }
        $this->setTemplate(file_get_contents($filename));
    }


    /**
     * Set the HTML to parse
     * @param $html
     */
    public function setTemplate($html) {
        $this->html = preg_replace("~<\!--\s*(BEGIN|END)\s+([a-zA-Z0-9_]+?)\s*-->~s", '<!-- $1 $2 -->', $html);
        $this->clear();
    }


    /**
     * Assign variable
     * @param string $var
     * @param string $value
     */
    public function assign($var, $value = '') {
        if ($this->reassign) $this->startReassign();
        $this->vars[$var] = $value;
    }


    /**
     * Reset the current instance's variables and make them able to assign again
     */
    public function reassign() {
        $this->reassign = true;
    }


    /**
     * Touched block
     * @param string $block
     */
    public function touchBlock($block) {
        $this->checkBlockName($block);
        $this->blocks[$block]['TOUCHED'] = true;
    }


    /**
     * Get html block
     * @param string $block
     * @return string|bool
     * @throws Exception
     */
    public function getBlock($block) {

        $this->checkBlockName($block);

        $begin_pos = strpos($this->html, "<!-- BEGIN {$block} -->")  + strlen("<!-- BEGIN {$block} -->");
        $end_pos   = strrpos($this->html, "<!-- END {$block} -->");

        if ($end_pos >= $begin_pos) {
            return substr($this->html, $begin_pos, $end_pos - $begin_pos);
        } else {
            throw new Exception("Block '{$block}' not found");
        }
    }


    /**
     * The final render
     * @return string
     */
    public function render() {
        $html = $this->html;

        if (strpos($html, 'BEGIN')) {
            $matches = array();
            preg_match_all("~<\!-- BEGIN ([a-zA-Z0-9_]+?) -->~s", $html, $matches);
            if (isset($matches[1]) && count($matches[1])) {
                foreach ($matches[1] as $block) {
                    if ( ! isset($this->blocks[$block])) {
                        $this->blocks[$block] = array('TOUCHED' => false);
                    }
                }
            }

            foreach ($this->blocks as $block => $data) {
                $block_begin = "<!-- BEGIN {$block} -->";
                $block_end   = "<!-- END {$block} -->";

                $begin_pos = strpos($html, $block_begin);
                $end_pos   = strrpos($html, $block_end);

                if ($begin_pos !== false && $end_pos !== false && $end_pos >= $begin_pos) {
                    $after_html  = substr($html, 0, $begin_pos);
                    $inside_html = substr($html, $begin_pos + strlen($block_begin), $end_pos - ($begin_pos + strlen($block_begin)));
                    $before_html = substr($html, $end_pos + strlen($block_end));

                    if (isset($data['TOUCHED']) && $data['TOUCHED']) {
                        $block_tpl = array_key_exists($block, $this->_p) ? $this->_p[$block] : null;
                        if ($block_tpl instanceof Micro_Templater) {
                            $parsed = $block_tpl->render();
                            $html = $after_html . $parsed . $before_html;
                        } else {
                            $html = $after_html . $inside_html . $before_html;
                        }

                    } else {
                        $html = $after_html . $before_html;
                    }
                }
            }
        }


        $assigned   = str_replace(array_keys($this->vars), $this->vars, $html);
        $html       = $this->loop . $assigned;
        $this->loop = '';

        return $html;
    }


    /**
     * Fill SELECT items on page
     * @param string       $selector
     * @param array        $options
     * @param string|array $selected
     */
    public function fillDropDown($selector, array $options, $selected = '') {

        if ($this->reassign) $this->startReassign();
        $doc  = new DOMDocument();
        $html = preg_replace('/&([a-zA-Z][a-zA-Z0-9]+);/s', '=[$1];',        $this->html);
        $html = preg_replace('~<!DOCTYPE([^>]*)>~s',        '!DOCTYPE[$1];', $html);
        $doc->loadXML('<root>' . $html . '</root>');
        $elements = $this->getDOMElements($selector, $doc);

        foreach ($elements as $key => $element) {
            if ($element instanceof DOMElement) {
                foreach ($options as $value => $option) {
                    if (is_array($option)) {
                        $optgroup = $doc->createElement("optgroup");
                        $optgroup->setAttribute('label', $value);

                        foreach ($option as $val => $opt) {
                            $node = $doc->createElement("option", $opt);
                            $node->setAttribute('value', $val);
                            if ($selected && ((is_array($selected) && in_array((string)$val, $selected)) || (string)$val === (string)$selected)) {
                                $node->setAttribute('selected', 'selected');
                            }
                            $optgroup->appendChild($node);
                        }
                        $element->appendChild($optgroup);

                    } else {
                        $node = $doc->createElement("option", $option);
                        $node->setAttribute('value', $value);
                        if ($selected && ((is_array($selected) && in_array((string)$value, $selected)) || (string)$value === (string)$selected)) {
                            $node->setAttribute('selected', 'selected');
                        }
                        $element->appendChild($node);
                    }
                }
            }
        }
        $xpath     = new DOMXpath($doc);
        $node_list = $xpath->evaluate('descendant-or-self::root');
        $this->html = $doc->saveXML($node_list->item(0), LIBXML_NOEMPTYTAG);
        $this->html = substr($this->html, 6, -7);
        $this->html = preg_replace('/=\[([a-zA-Z][a-zA-Z0-9]+)\];/s', '&$1;',         $this->html);
        $this->html = preg_replace('/!DOCTYPE\[(.*?)\];/s',           '<!DOCTYPE$1>', $this->html);
    }


    /**
     * Setting the value of the attribute
     * @param  string     $selector
     * @param  string     $name
     * @param  string     $value
     * @throws Exception
     * @return self
     */
    public function setAttr($selector, $name, $value) {
        if (is_string($selector) && is_string($name) && is_string($value)) {
            $html = preg_replace('/&([a-zA-Z][a-zA-Z0-9]+);/s', '=[$1];',        $this->html);
            $html = preg_replace('~<!DOCTYPE([^>]*)>~s',        '!DOCTYPE[$1];', $html);
            $doc  = new DOMDocument();
            $doc->loadXML('<root>' . $html . '</root>');
            $elements = $this->getDOMElements($selector, $doc);

            foreach ($elements as $key => $element) {
                if ($element instanceof DOMElement) {
                    $element->setAttribute($name, $value);
                }
            }

            $xpath     = new DOMXpath($doc);
            $node_list = $xpath->evaluate('descendant-or-self::root');
            $this->html = $doc->saveXML($node_list->item(0), LIBXML_NOEMPTYTAG);
            $this->html = substr($this->html, 6, -7);
            $this->html = preg_replace('/=\[([a-zA-Z][a-zA-Z0-9]+)\];/s', '&$1;',         $this->html);
            $this->html = preg_replace('/!DOCTYPE\[(.*?)\];/s',           '<!DOCTYPE$1>', $this->html);
        } else {
            throw new Exception("Wrong type for input parameters 'selector', 'name' or 'value'. Need string");
        }
        return $this;
    }


    /**
     * Setting the value at the beginning of the attribute
     * @param  string     $selector
     * @param  string     $name
     * @param  string     $value
     * @throws Exception
     * @return self
     */
    public function setPrependAttr($selector, $name, $value) {
        if (is_string($selector) && is_string($name) && is_string($value)) {
            $doc  = new DOMDocument();
            $html = preg_replace('/&([a-zA-Z][a-zA-Z0-9]+);/s', '=[$1];',        $this->html);
            $html = preg_replace('~<!DOCTYPE([^>]*)>~s',        '!DOCTYPE[$1];', $html);
            $doc->loadXML('<root>' . $html . '</root>');
            $elements = $this->getDOMElements($selector, $doc);

            foreach ($elements as $key => $element) {
                if ($element instanceof DOMElement) {
                    if ($element->hasAttribute($name)) {
                        $value .= $element->getAttribute($name);
                    }
                    $element->setAttribute($name, $value);
                }
            }

            $xpath     = new DOMXpath($doc);
            $node_list = $xpath->evaluate('descendant-or-self::root');
            $this->html = $doc->saveXML($node_list->item(0), LIBXML_NOEMPTYTAG);
            $this->html = substr($this->html, 6, -7);
            $this->html = preg_replace('/=\[([a-zA-Z][a-zA-Z0-9]+)\];/s', '&$1;',         $this->html);
            $this->html = preg_replace('/!DOCTYPE\[(.*?)\];/s',           '<!DOCTYPE$1>', $this->html);
        } else {
            throw new Exception("Wrong type for input parameters 'selector', 'name' or 'value'. Need string");
        }
        return $this;
    }


    /**
     * Setting the value of the attribute at the end
     * @param  string     $selector
     * @param  string     $name
     * @param  string     $value
     * @throws Exception
     * @return self
     */
    public function setAppendAttr($selector, $name, $value) {
        if (is_string($selector) && is_string($name) && is_string($value)) {
            $doc  = new DOMDocument();
            $html = preg_replace('/&([a-zA-Z][a-zA-Z0-9]+);/s', '=[$1];',        $this->html);
            $html = preg_replace('~<!DOCTYPE([^>]*)>~s',        '!DOCTYPE[$1];', $html);
            $doc->loadXML('<root>' . $html . '</root>');
            $elements = $this->getDOMElements($selector, $doc);

            foreach ($elements as $key => $element) {
                if ($element instanceof DOMElement) {
                    if ($element->hasAttribute($name)) {
                        $old   = $element->getAttribute($name);
                        $value = $old . $value;
                    }
                    $element->setAttribute($name, $value);
                }
            }

            $xpath     = new DOMXpath($doc);
            $node_list = $xpath->evaluate('descendant-or-self::root');
            $this->html = $doc->saveXML($node_list->item(0), LIBXML_NOEMPTYTAG);
            $this->html = substr($this->html, 6, -7);
            $this->html = preg_replace('/=\[([a-zA-Z][a-zA-Z0-9]+)\];/s', '&$1;',         $this->html);
            $this->html = preg_replace('/!DOCTYPE\[(.*?)\];/s',           '<!DOCTYPE$1>', $this->html);
        } else {
            throw new Exception("Wrong type for input parameters 'selector', 'name' or 'value'. Need string");
        }
        return $this;
    }


    /**
     * Setting attributes
     * @param  string     $selector
     * @param  array      $attributes
     * @throws Exception
     * @return self
     */
    public function setAttributes($selector, array $attributes) {
        if (is_string($selector) && is_array($attributes)) {
            $doc  = new DOMDocument();
            $html = preg_replace('/&([a-zA-Z][a-zA-Z0-9]+);/s', '=[$1];',        $this->html);
            $html = preg_replace('~<!DOCTYPE([^>]*)>~s',        '!DOCTYPE[$1];', $html);
            $doc->loadXML('<root>' . $html . '</root>');
            $elements = $this->getDOMElements($selector, $doc);

            foreach ($elements as $key => $element) {
                if ($element instanceof DOMElement) {
                    foreach ($attributes as $name => $value) {
                        $element->setAttribute($name, $value);
                    }
                }
            }

            $xpath     = new DOMXpath($doc);
            $node_list = $xpath->evaluate('descendant-or-self::root');
            $this->html = $doc->saveXML($node_list->item(0), LIBXML_NOEMPTYTAG);
            $this->html = substr($this->html, 6, -7);
            $this->html = preg_replace('/=\[([a-zA-Z][a-zA-Z0-9]+)\];/s', '&$1;',         $this->html);
            $this->html = preg_replace('/!DOCTYPE\[(.*?)\];/s',           '<!DOCTYPE$1>', $this->html);
        } else {
            throw new Exception("Wrong type for input parameters 'selector' or 'attributes'. Need string and array");
        }
        return $this;
    }


    /**
     * Setting the value at the beginning of the attribute
     * @param  string     $selector
     * @param  array      $attributes
     * @throws Exception
     * @return self
     */
    public function setPrependAttributes($selector, array $attributes) {
        if (is_string($selector) && is_array($attributes)) {
            $doc  = new DOMDocument();
            $html = preg_replace('/&([a-zA-Z][a-zA-Z0-9]+);/s', '=[$1];',        $this->html);
            $html = preg_replace('~<!DOCTYPE([^>]*)>~s',        '!DOCTYPE[$1];', $html);
            $doc->loadXML('<root>' . $html . '</root>');
            $elements = $this->getDOMElements($selector, $doc);

            foreach ($elements as $key => $element) {
                if ($element instanceof DOMElement) {
                    foreach ($attributes as $name => $value) {
                        if ($element->hasAttribute($name)) {
                            $value .= $element->getAttribute($name);
                        }
                        $element->setAttribute($name, $value);
                    }
                }
            }

            $xpath     = new DOMXpath($doc);
            $node_list = $xpath->evaluate('descendant-or-self::root');
            $this->html = $doc->saveXML($node_list->item(0), LIBXML_NOEMPTYTAG);
            $this->html = substr($this->html, 6, -7);
            $this->html = preg_replace('/=\[([a-zA-Z][a-zA-Z0-9]+)\];/s', '&$1;',         $this->html);
            $this->html = preg_replace('/!DOCTYPE\[(.*?)\];/s',           '<!DOCTYPE$1>', $this->html);
        } else {
            throw new Exception("Wrong type for input parameters 'selector' or 'attributes'. Need string and array");
        }
        return $this;
    }


    /**
     * Setting the value of the attribute at the end
     * @param  string     $selector
     * @param  array      $attributes
     * @throws Exception
     * @return self
     */
    public function setAppendAttributes($selector, array $attributes) {
        if (is_string($selector) && is_array($attributes)) {
            $doc  = new DOMDocument();
            $html = preg_replace('/&([a-zA-Z][a-zA-Z0-9]+);/s', '=[$1];',        $this->html);
            $html = preg_replace('~<!DOCTYPE([^>]*)>~s',        '!DOCTYPE[$1];', $html);
            $doc->loadXML('<root>' . $html . '</root>');
            $elements = $this->getDOMElements($selector, $doc);
            foreach ($elements as $key => $element) {
                if ($element instanceof DOMElement) {
                    foreach ($attributes as $name => $value) {
                        if ($element->hasAttribute($name)) {
                            $old   = $element->getAttribute($name);
                            $value = $old . $value;
                        }
                        $element->setAttribute($name, $value);
                    }
                }
            }
            $xpath     = new DOMXpath($doc);
            $node_list = $xpath->evaluate('descendant-or-self::root');
            $this->html = $doc->saveXML($node_list->item(0), LIBXML_NOEMPTYTAG);
            $this->html = substr($this->html, 6, -7);
            $this->html = preg_replace('/=\[([a-zA-Z][a-zA-Z0-9]+)\];/s', '&$1;',         $this->html);
            $this->html = preg_replace('/!DOCTYPE\[(.*?)\];/s',           '<!DOCTYPE$1>', $this->html);
        } else {
            throw new Exception("Wrong type for input parameters 'selector' or 'attributes'. Need string and array");
        }
        return $this;
    }


    /**
     * Get an array of elements
     * @param  string       $selector
     * @param  DOMDocument $doc
     * @return array
     */
    protected function getDOMElements($selector, DOMDocument $doc) {
        $selector  = preg_replace('/\s*([>~+,])\s*/', '$1', $selector);
        $selectors = preg_split("/\s+(?![^\[]+\])/", $selector);
        foreach ($selectors as $key => $selector) {
            // ,
            $selectors[$key] = preg_replace('/\s*,\s*/', '|descendant-or-self::', $selector);
            // :button, :submit, etc
            $selectors[$key] = preg_replace('/:(button|submit|file|checkbox|radio|image|reset|text|password)/', 'input[@type="\1"]', $selectors[$key]);
            // [id]
            $selectors[$key] = preg_replace('/\[(\w+)\]/', '*[@\1]', $selectors[$key]);
            // foo[id=foo]
            $selectors[$key] = preg_replace('/\[(\w+)=[\'"]?(.*?)[\'"]?\]/', '[@\1="\2"]', $selectors[$key]);
            // [id=foo]
            $selectors[$key] = preg_replace('/^\[/', '*[', $selectors[$key]);
            // div#foo
            $selectors[$key] = preg_replace('/([\w\-]+)\#([\w\-]+)/', '\1[@id="\2"]', $selectors[$key]);
            // #foo
            $selectors[$key] = preg_replace('/\#([\w\-]+)/', '*[@id="\1"]', $selectors[$key]);
            // div.foo
            $selectors[$key] = preg_replace('/([\w\-]+)\.([\w\-]+)/', '\1[contains(concat(" ",@class," ")," \2 ")]', $selectors[$key]);
            // .foo
            $selectors[$key] = preg_replace('/\.([\w\-]+)/', '*[contains(concat(" ",@class," ")," \1 ")]', $selectors[$key]);
            // div:first-child
            $selectors[$key] = preg_replace('/([\w\-]+):first-child/', '*/\1[position()=1]', $selectors[$key]);
            // div:last-child
            $selectors[$key] = preg_replace('/([\w\-]+):last-child/', '*/\1[position()=last()]', $selectors[$key]);
            // :first-child
            $selectors[$key] = str_replace(':first-child', '*/*[position()=1]', $selectors[$key]);
            // :last-child
            $selectors[$key] = str_replace(':last-child', '*/*[position()=last()]', $selectors[$key]);
            // :nth-last-child
            $selectors[$key] = preg_replace('/:nth-last-child\((\d+)\)/', '[position()=(last() - (\1 - 1))]', $selectors[$key]);
            // div:nth-child
            $selectors[$key] = preg_replace('/([\w\-]+):nth-child\((\d+)\)/', '*/*[position()=\2 and self::\1]', $selectors[$key]);
            // :nth-child
            $selectors[$key] = preg_replace('/:nth-child\((\d+)\)/', '*/*[position()=\1]', $selectors[$key]);
            // :contains(Foo)
            $selectors[$key] = preg_replace('/([\w\-]+):contains\((.*?)\)/', '\1[contains(string(.),"\2")]', $selectors[$key]);
            // >
            $selectors[$key] = preg_replace('/\s*>\s*/', '/', $selectors[$key]);
            // ~
            $selectors[$key] = preg_replace('/\s*~\s*/', '/following-sibling::', $selectors[$key]);
            // +
            $selectors[$key] = preg_replace('/\s*\+\s*([\w\-]+)/', '/following-sibling::\1[position()=1]', $selectors[$key]);
            $selectors[$key] = str_replace(']*', ']', $selectors[$key]);
            $selectors[$key] = str_replace(']/*', ']', $selectors[$key]);
        }
        $selector = implode('/descendant::', $selectors);
        $selector = 'descendant-or-self::' . $selector;
        $xpath    = new DOMXpath($doc);
        $elements = $xpath->evaluate($selector);
        $array = array();
        if ($elements instanceof DOMNodeList && $elements->length) {
            for ($i = 0, $length = $elements->length; $i < $length; ++$i) {
                if ($elements->item($i)->nodeType == XML_ELEMENT_NODE) {
                    $array[] = $elements->item($i);
                }
            }
        }
        return $array;
    }


    /**
     * Clear vars & blocks
     */
    protected function clear() {
        $this->blocks = array();
        $this->vars   = array();
        foreach ($this->_p as $obj) {
            if ($obj instanceof Micro_Templater) {
                $obj->clear();
            }
        }
    }


    /**
     * Start reassign
     */
    protected function startReassign() {
        $this->loop = $this->render();
        $this->clear();
        $this->reassign = false;
    }


    /**
     * Check block name
     * @param $block
     * @return void
     * @throws Exception
     */
    protected function checkBlockName($block) {

        if ($block === '') {
            throw new Exception("Block name '{$block}' must be a non-empty string");
        }

        if (preg_match('~^\d~', $block[0])) {
            throw new Exception("Block name '{$block}' must not start with a number");
        }

        if (preg_match('~[^0-9a-zA-Z_]~', $block)) {
            throw new Exception("Block name '{$block}' contents wrong chars");
        }
    }
}