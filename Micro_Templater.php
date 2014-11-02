<?php



/**
 * Class Micro_Templater
 * @see https://github.com/shinji00/Micro_Templater
 */
class Micro_Templater {

    private $loopHTML = array();
    private $plugins  = array();
    private $blocks   = array();
    private $vars     = array();
    private $_p       = array();
    private $html     = '';

    public function __construct($tpl_file = '') {
        if ($tpl_file) $this->loadTemplate($tpl_file);
    }

    public function __isset($block) {
        return isset($this->_p[$block]);
    }


    /**
     * Nested blocks will be stored inside $_p
     * @param string $field
     * @return Micro_Templater|null
     */
    public function __get($field) {
        if (array_key_exists($field, $this->_p)) {
            $v = $this->_p[$field];
        } else {
            $temp = new Micro_Templater();
            $temp->setTemplate($this->getBlock($field));
            $v = $this->_p[$field] = $temp;
        }
        return $v;
    }


    /**
     * Load the HTML file to parse
     * @param string $path
     * @throws \Exception
     */
    public function loadTemplate($path) {
        if ( ! file_exists($path)) {
            throw new \Exception("file not found \"$path\"");
        }
        $this->setTemplate(file_get_contents($path));
    }


    /**
     * Set the HTML to parse
     * @param $html
     */
    public function setTemplate($html) {
        $this->html = $html;
        $this->clear();
    }


    /**
     * Touched block
     * @param string $block
     */
    public function touchBlock($block) {
        $this->blocks[$block]['TOUCHED'] = true;
    }


    /**
     * Get html block
     * @param string $block
     * @return string
     */
    public function getBlock($block) {
        $matched = array();
        preg_match("~<\!--\s*BEGIN\s+{$block}\s*-->(.+)<\!--\s*END\s+{$block}\s*-->~s", $this->html, $matched);
        return ! empty($matched[1]) ? $matched[1] : '';
    }


    /**
     * Add plugin
     * @param string $title
     * @param mixed $obj
     */
    public function addPlugin($title, $obj) {
        $this->plugins[strtolower($title)] = $obj;
    }


    /**
     * Assign variable
     * @param string $var
     * @param string $value
     */
    public function assign($var, $value = '') {
        $this->vars[$var] = $value;
    }


    /**
     * Reset the current instance's variables and make them able to assign again
     */
    public function reassign() {
        $this->loopHTML[] = $this->parse();
        $this->clear();
        $this->setTemplate($this->html);
    }


    /**
     * Fill SELECT items on page
     * @param string $id
     * @param array $options
     * @param string|array $selected
     */
    public function fillDropDown($id, array $options, $selected = '') {
        $html = "";
        foreach ($options as $value => $title) {
            $sel = (is_array($selected) && in_array($value, $selected)) || $value == $selected
                ? "selected=\"selected\" "
                : '';
            $html .= "<option {$sel}value=\"{$value}\">{$title}</option>";
        }
        if ($html) {
            $id = preg_quote($id);
            $reg = "~(<select.*?id\s*=\s*[\"']{$id}[\"'][^>]*>).*?(</select>)~si";
            $this->html = preg_replace($reg, "$1[[$id]]$2", $this->html);
            $this->assign("[[$id]]", $html, true);
        }
    }


    /**
     * The final render
     * @return string
     */
    public function parse() {
        $html = $this->html;

        if (strpos($html, 'BEGIN')) {
            $matches = array();
            preg_match_all("~<\!--\s*BEGIN\s(.+?)\s*-->~s", $html, $matches);
            if (isset($matches[1]) && count($matches[1])) {
                foreach ($matches[1] as $block) {
                    if ( ! isset($this->blocks[$block])) {
                        $this->blocks[$block] = array('TOUCHED' => false);
                    }
                }
            }

            foreach ($this->blocks as $block => $data) {
                $sub_tpl = array_key_exists($block, $this->_p) ? $this->_p[$block] : null;
                $html    = preg_replace_callback(
                    "~(.*)<\!--\s*BEGIN\s+{$block}\s*-->(.+)<\!--\s*END\s+{$block}\s*-->(.*)~s",
                    function($matches) use($block, $data, $sub_tpl) {
                        if ($sub_tpl) {
                            $parsed = $sub_tpl->parse();
                            $html   = $matches[1] . $parsed . $matches[3];

                        } elseif (isset($data['TOUCHED']) && $data['TOUCHED']) {
                            $html = $matches[1] . $matches[2] . $matches[3];

                        } else {
                            $html = $matches[1] . $matches[3];
                        }

                        return $html;
                    },
                    $html
                );
            }
        }

        $loop     = implode('', $this->loopHTML);
        $assigned = str_replace(array_keys($this->vars), $this->vars, $html);
        $html     = $loop . $assigned;

        $this->loopHTML = array();

        //apply plugins
        foreach ($this->plugins as $plugin => $process) {
            $matches = array();
            preg_match_all("~\[{$plugin}:([^\]]+)\]~s", $html, $matches);
            foreach ($matches[1] as $k => $value) {
                $tmp = explode('|', $value);
                $matches[1][$k] = call_user_func_array(array($process, $plugin), $tmp);
            }
            $html = str_replace($matches[0], $matches[1], $html);
        }

        return $html;
    }


    /**
     * Clear vars & blocks
     */
    private function clear() {
        $this->blocks = array();
        $this->vars   = array();
        foreach ($this->_p as $obj => $data) {
            $this->_p[$obj]->clear();
        }
    }
}