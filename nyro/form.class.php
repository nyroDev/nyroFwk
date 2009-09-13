<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 * Final class for form
 */
class form extends object {

	/**
	 * Form elements
	 *
	 * @var array
	 */
	protected $elements = array();

	/**
	 * i18n Form elements
	 *
	 * @var array
	 */
	protected $i18nElements = array();

	/**
	 * Section name
	 *
	 * @var array
	 */
	protected $section = array();

	/**
	 * Save the section for the elements
	 *
	 * @var array
	 */
	protected $elementsSection = array();

	/**
	 * Current section index
	 *
	 * @var array
	 */
	protected $curSection = 0;

	/**
	 * Number files input
	 *
	 * @var int
	 */
	protected $hasFiles = 0;

	/**
	 * Indicates if the form has been bound
	 *
	 * @var boolean
	 */
	protected $isBound = false;

	/**
	 * Errors for the last validation
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Errors manually added
	 *
	 * @var array
	 */
	protected $customErrors = array();
	
	/**
	 * Indicate if a captcha was already added
	 *
	 * @var bool
	 */
	protected $captchaAdded = false;

	/**
	 * Add the first section from the configuration file
	 */
	protected function afterInit() {
		$this->addSection($this->cfg->sectionName);
	}

	/**
	 * Set the submit text
	 *
	 * @param string $text
	 */
	public function setSubmitText($text) {
		$this->cfg->submitText = $text;
	}

	/**
	 * Set the submit plus
	 *
	 * @param string $text
	 */
	public function setSubmitPlus($text) {
		$this->cfg->submitPlus = $text;
	}

	/**
	 * Transform the element to a string to be shown, with the courant output
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->to(request::get('out'));
	}

	/**
	 * Transform the element to a string to be shown
	 *
	 * @param string $type The output type
	 * @return string
	 */
	public function to($type) {
		$this->addCaptcha();
		$ret = null;

		$prm = $this->cfg->get($type);
		if ($this->cfg->check($tmp = $type.ucfirst($this->cfg->mode)))
			$prm = array_merge($prm, $this->cfg->get($tmp));

		if (!$this->cfg->showSection) {
			$prm = array_merge($prm, $this->cfg->get($type.'NoSection'));
			if ($this->cfg->check($tmp = $type.'NoSection'.ucfirst($this->cfg->mode)))
				$prm = array_merge($prm, $this->cfg->get($tmp));
		}

		$hiddenGlobal = (array_key_exists('noHidden', $prm) && $prm['noHidden']) || !(strpos($prm['global'], '[hidden]') === false);
		$hiddens = null;

		$errorPos = $prm['errorPos'];
		$errorsGlobal = array();

		foreach($this->section as $kSection=>$sectionName) {
			$fields = null;
			$errorsSection = array();
			foreach($this->elements[$kSection] as $name=>$e) {
				$des = !empty($e->description)? str_replace('[des]', $e->description, $prm['des']) : null;
				$line = $e->isHidden()? 'lineHidden' : 'line';

				$errors = null;
				if ($this->isBound() && !$e->isValid() && !$e->isHidden()) {
					$tmp = array();
					foreach($e->getErrors() as $err) {
						$tmp[] = str_replace('[error]', $err, $prm['lineErrorLine']);
						$errorsSection[] = str_replace('[error]', $err, $prm['sectionErrorLine']);
						$errorsGlobal[] = str_replace('[error]', $err, $prm['globalErrorLine']);
					}
					$errors = $errorPos == 'field'?str_replace('[errors]', implode('', $tmp), $prm['lineErrorWrap']) : null;
					$line = 'lineError';
				}

				$label = $e->label?$e->label.$this->cfg->sepLabel : $this->cfg->emptyLabel;
				$tmp = str_replace(
					array('[des]', '[label]', '[field]', '[errors]', '[id]', '[classLine]'),
					array($des, $label, $e->to($type), $errors, $e->id, $e->classLine),
					$prm[$line]);
				if ($e->isHidden() && $hiddenGlobal)
					$hiddens.= $tmp;
				else
					$fields.= $tmp;
			}
			if ($fields) {
				$errors = null;
				$section = 'section';
				if (!empty($errorsSection) && $errorPos == 'section') {
					$errors = implode('', $errorsSection);
					$section.= 'Error';
				}
				$ret.= str_replace(
					array('[errors]', '[fields]', '[label]'),
					array($errors, $fields, utils::htmlOut($sectionName)),
					$prm[$section]);
			}
		}

		$plus = null;
		if ($type == 'html') {
			if (array_key_exists('incFiles', $prm))
				foreach($prm['incFiles'] as $f)
					response::getInstance()->add($f);

			$plus = 'action="'.request::uri($this->cfg->action).'" method="'.$this->cfg->method.'"';
			if ($this->hasFiles)
				$plus.= ' enctype="multipart/form-data"';
		}
		$plus.= $this->cfg->formPlus;

		$errors = null;
		if (!empty($errorsGlobal) && $errorPos == 'global') {
			$errors = str_replace('[errors]', implode('', $errorsGlobal), $prm['globalError']);
		}
		return str_replace(
			array('[hidden]', '[errors]', '[content]', '[plus]', '[submit]', '[submitText]', '[submitPlus]'),
			array($hiddens, $errors, $ret, $plus, $prm['submit'], $this->cfg->submitText, $this->cfg->submitPlus),
			$prm['global']);
	}

	/**
	 * Finalize the construction of the form. Should be call when i18n are present
	 */
	public function finalize() {
		if ($this->isI18n()) {
			$this->cfg->showSection = true;
			foreach(request::avlLang(true) as $lg=>$lang) {
				$this->addSection($lang);
				foreach($this->i18nElements as $e) {
					$e['prm']['isI18n'] = true;
					$e['prm']['name'] = db::getCfg('i18n').'['.$lg.']['.$e['prm']['name'].']';
					$this->add($e['type'], $e['prm']);
				}
			}
		}
	}

	/**
	 * Transform the form in HTML
	 */
	public function toHtml() {
		return $this->to('html');
	}

	/**
	 * Transform the form in XUL
	 */
	public function toXul() {
		return $this->to('xul');
	}

	/**
	 * Check if all the form elements are valid
	 *
	 * @return bool True if valid
	 */
	public function isValid() {
		$validRet = true;
		$this->errors = array();
		foreach($this->section as $kSection=>$sectionName) {
			foreach($this->elements[$kSection] as $name=>$e) {
				$valid = $e->isValid();
				if (!$valid)
					$this->errors[$name] = $e->getErrors();
				$validRet = $validRet && $valid;
			}
		}
		return $validRet;
	}

	/**
	 * Indicate if the form has i18n elements
	 *
	 * @return bool
	 */
	public function isI18n() {
		return !empty($this->i18nElements);
	}

	/**
	 * Get all the errors for the last validation
	 *
	 * @return array
	 */
	public function getErrors() {
		return array_merge_recursive($this->errors, $this->customErrors);
	}

	/**
	 * Check if the form has errors (only if a validations was done)
	 *
	 * @return bool
	 */
	public function hasErrors() {
		return !empty($this->errors) || !empty($this->customErrors);
	}
	
	/**
	 * Add a custom error
	 *
	 * @param string $field Filed name to be associate with
	 * @param string $error The error text
	 */
	public function addCustomError($field, $error) {
		if ($elt = $this->get($field)) {
			$elt->addCustomError($error);
			return;
		}
		if (!array_key_exists($field, $this->customErrors))
			$this->customErrors[$field] = array();
		$this->customErrors[$field][] = $error;
	}

	/**
	 * Add a form element in the current section
	 *
	 * @param string|form_abstract $type Form element type or element form
	 * @param array $prm Parameter array for the element
	 * @param bool $isI18n
	 * @return form_abstract|null Reference to the added element or null if not added or i18n (name exist yet)
	 */
	public function add($type, array $prm=array(), $isI18n=false) {
		if ($isI18n) {
			$this->i18nElements[] = array('type'=>$type, 'prm'=>$prm);
			return null;
		}
		$inst = null;
		$name = null;
		if ($type instanceof form_abstract && !$this->has($name = $type->getName())) {
			$inst = $type;
		} else if (is_string($type) && array_key_exists('name', $prm) && !$this->has($name = $prm['name'])) {
			$inst = $this->getNew($type, $prm);
		}
		if ($inst) {
			$this->elements[$this->curSection][$name] = $inst;
			$this->elementsSection[$name] = $this->curSection;

			if ($inst instanceof form_file)
				$this->hasFiles++;

			return $inst;
		} else
			return null;
	}

	/**
	 * Check if the form has a element
	 *
	 * @param string $name Field name
	 * @return bool
	 */
	public function has($name) {
		return array_key_exists($name, $this->elementsSection);
	}

	/**
	 * Get a form element
	 *
	 * @param string $name Field name
	 * @return form_abstract|null
	 */
	public function get($name) {
		if ($this->has($name))
			return $this->elements[$this->elementsSection[$name]][$name];
		return null;
	}

	/**
	 * Delete a form element
	 *
	 * @param string $name
	 */
	public function del($name) {
		if ($this->has($name)) {
			unset($this->elements[$this->elementsSection[$name]][$name]);
			unset($this->elementsSection[$name]);
		}
	}

	/**
	 * Reorder the fields in the current section
	 *
	 * @param array $order Array containing the field names in order wanted
	 */
	public function reOrder(array $order) {
		$tmp = array();
		foreach($order as $v) {
			$e = $this->get($v);
			if ($e)
				$tmp[$v] = $e;
		}
		$this->elements[$this->getSection()] = $tmp;
	}

	/**
	 * Get the actual value for 1 field
	 *
	 * @param string $name Field name
	 * @return mixed
	 */
	public function getValue($name) {
		if ($elm = $this->get($name)) {
			return $elm->getValue();
		}
		return null;
	}

	/**
	 * Get all the values field
	 *
	 * @param bool $onlyFilled
	 * @param bool $ignoreWhitePassword
	 * @return array
	 */
	public function getValues($onlyFilled=false, $ignoreWhitePassword=true) {
		$ret = array();

		foreach($this->elementsSection as $name=>$section) {
			if (!($ignoreWhitePassword && $this->get($name) instanceof form_password && !$this->getValue($name)))
				$ret[$name] = $this->getValue($name);
		}

		$ret = array_diff_key($ret, $this->cfg->notValue);

		if ($onlyFilled)
			$ret = array_filter($ret, create_function('$v', 'return $v != "";'));

		$tmp = array();
		foreach($ret as $k=>$v) {
			$matches = explode('|', str_replace(
				array('][', '[', ']'),
				array('|', '|', ''),
				$k
			));
			if (count($matches) > 1) {
				$t = &$tmp;
				for($i = 0; $i < count($matches); $i++) {
					if (!isset($matches[$i+1]))
						$t[$matches[$i]] = $v;
					else {
						if (!array_key_exists($matches[$i], $t) || !is_array($t[$matches[$i]]))
							$t[$matches[$i]] = array();
						$t = &$t[$matches[$i]];
					}
				}
			} else
				$tmp[$matches[0]] = $v;
		}
		return $tmp;
	}

	/**
	 * Add field to be considered as a non value (ie non attribued in getValues)
	 *
	 * @param string $name
	 */
	public function addNotValue($name) {
		$this->cfg->setInArray('notValue', $name, true);
	}

	/**
	 * Get all the available names
	 *
	 * @return array
	 */
	public function getNames() {
		return array_keys($this->elementsSection);
	}

	/**
	 * Set a field value
	 *
	 * @param string $name Field name
	 * @param mixed $value
	 * @param boolean $refill Indicate if the value is a refill one
	 * @return bool True if successful
	 */
	public function setValue($name, $value, $refill=false) {
		if ($elm = $this->get($name)) {
			$elm->setValue($value, $refill);
			$this->isBound = true;
			return true;
		}
		return false;
	}

	/**
	 * Set values to the form elements
	 *
	 * @param array $data
	 * @param boolean $refill Indicate if the value is a refill one
	 * @return int Element values updated count
	 */
	public function setValues(array $data, $refill=false) {
		$i = 0;
		foreach($data as $name=>$value) {
			if ($this->setValue($name, $value, $refill))
				$i++;
		}
		return $i;
	}

	/**
	 * Indicates if the form has been bound
	 *
	 * @return boolean
	 */
	public function isBound() {
		return $this->isBound;
	}

	/**
	 * Set the bound status
	 *
	 * @param boolean $bound
	 */
	public function setBound($bound) {
		$this->isBound = $bound;
	}

	/**
	 * Refill the whole form from the post argument
	 */
	public function refill() {
		$this->addCaptcha();
		$htVars = http_vars::getInstance();
		foreach($this->elementsSection as $name=>$section) {
			$val = $htVars->getVar(array(
				'name'=>$name,
				'method'=>$this->cfg->method
			));
			$this->setValue($name, $val, true);
		}
	}

	/**
	 * Get a form element for the form type
	 *
	 * @param string $type Form element type
	 * @param array $prm Parameter array for the element
	 * @return form_abstract Reference to the new element
	 */
	public function getNew($type, array $prm) {
		if (!array_key_exists('mode', $prm))
			$prm['mode'] = $this->cfg->mode;
		return factory::get('form_'.$type, $prm);
	}

	/**
	 * Add a new section
	 *
	 * @param string $name Section name
	 * @return int The section index
	 */
	public function addSection($name) {
		$this->curSection = count($this->section);
		$this->section[$this->curSection] = $name;
		$this->elements[$this->curSection] = array();
		return $this->curSection;
	}

	/**
	 * Set the current section
	 *
	 * @param int|string $search Section index or section name
	 * @return false|int Section index if found or false
	 */
	public function setSection($search) {
		$find = false;
		if (!is_int($search)) {
			foreach($this->section as $k=>&$s) {
				if ($s == $search)
					$find = $k;
			}
		} else
			$find = $search;

		if (is_int($find) && $find < count($this->elements))
			$this->curSection = $find;

		return $find;
	}

	/**
	 * Change the mode of the form
	 *
	 * @param string $mode edit or view
	 * @param bool $force True to reaffect all the field
	 */
	public function setMode($mode, $force=true) {
		$this->cfg->mode = $mode;
		if ($force) {
			foreach($this->section as $kSection=>$sectionName)
				foreach($this->elements[$kSection] as $name=>$e)
					$e->mode = $mode;
		}
	}

	/**
	 * Set the section to the first
	 */
	public function firstSection() {
		$this->curSection = 0;
	}

	/**
	 * Set the section to the last
	 */
	public function lastSection() {
		$this->curSection = count($this->elements)-1;
	}

	/**
	 * Get the current section index
	 *
	 * @return int
	 */
	public function getSection() {
		return $this->curSection;
	}
	
	/**
	 * Add a captcha if parametred and not already added
	 */
	protected function addCaptcha() {
		if (!$this->captchaAdded) {
			if (($typeCpt = $this->cfg->getInarray('captcha', 'type')) && ($nameCpt = $this->cfg->getInarray('captcha', 'name'))) {
				$this->add($typeCpt, $this->cfg->captcha);
				$this->captchaAdded = true;
				$this->cfg->setInArray('notValue', $nameCpt, $nameCpt);
			}
		}
	}

	/**
	 * Set configuration var with a depth of 2
	 *
	 * @param string $key1 The 1st index
	 * @param string $key2 The 2nd index
	 * @param mixed $val The value
	 */
	public function set($key1, $key2, $val) {
		$this->cfg->setInArray($key1, $key2, $val);
	}

	public function __set($key, $val) {
		$this->cfg->set($key, $val);
	}

	/**
	 * Used when cloning the form to create new field element
	 */
	public function __clone() {
		$this->cfg = new config($this->cfg->getAll());
		$bound = $this->isBound;
		foreach($this->section as $kSection=>$sectionName) {
			foreach($this->elements[$kSection] as $name=>$e) {
				$this->elements[$kSection][$name] = factory::get(get_class($e), unserialize(serialize($e->getCfg()->getAll())));
				if (!is_object($e->getValue()))
					$this->setValue($name, $e->getValue());
			}
		}
		$this->isBound = $bound;
	}

}