<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
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
		try {
			return $this->to(request::get('out'));
		} catch (Exception $e) {
			if (DEV) {
				debug::trace($e, 2);
			} else
			   throw $e;
		}
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
				$des = $e->description? str_replace('[des]', $e->getDescription(), $prm['des']) : null;
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

				$requiredMoreLabel = $e->getValidRule('required') ? $this->cfg->requiredMoreLabel : null;
				$label = $e->label
						? $e->label.$requiredMoreLabel.$this->cfg->sepLabel
						: $this->cfg->emptyLabel;
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

			$plus = 'action="'.$this->cfg->action.'" method="'.$this->cfg->method.'"';
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
			$nb = 0;
			if ($this->cfg->forceOnlyOneLang || $this->cfg->noForceLang)
				$requiredFields = array();
			foreach(request::avlLang(true) as $lg=>$lang) {
				$this->addSection($lang);
				$groupedFieldsAdded = false;
				foreach($this->i18nElements as $e) {
					$e['prm']['isI18n'] = true;
					$initName = $e['prm']['name'];
					$e['prm']['name'] = db::getCfg('i18n').'['.$lg.']['.$initName.']';
					if ($this->cfg->forceOnlyOneLang || $this->cfg->noForceLang) {
						if ($nb == 0) {
							if ($e['prm']['valid']['required'])
								$requiredFields[] = $initName;
						} else {
							if (!$groupedFieldsAdded && $e['prm']['valid']['required'] && count($requiredFields)) {
								$fields = array();
								foreach($requiredFields as $v)
									$fields[] = db::getCfg('i18n').'['.$lg.']['.$v.']';
								$e['prm']['valid']['groupedFields'] = array(
									'form'=>$this,
									'fields'=>$fields
								);
								$groupedFieldsAdded = true;
							}
						}
						if ($nb > 0 || $this->cfg->noForceLang)
							$e['prm']['valid']['required'] = false;
					}
					$this->add($e['type'], $e['prm']);
				}
				$nb++;
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
		$validRet = empty($this->customErrors);
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
			$inst->renew();
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
			if (isset($this->elementsSection[$v]) && $this->elementsSection[$v] == $this->curSection) {
				$e = $this->get($v);
				if ($e)
					$tmp[$v] = $e;
			}
		}
		foreach($this->elements[$this->curSection] as $name=>$elt) {
			if (!isset($tmp[$name]))
				$tmp[$name] = $elt;
		}
		$this->elements[$this->curSection] = $tmp;
	}

	/**
	 * Reorder the sections
	 *
	 * @param array $order Array containing either the section index or the section name
	 */
	public function reOrderSection(array $order) {
		$section = $elements = $elementsSection = $trans = array();
		$cur = 0;
		foreach($order as $v) {
			$old = is_int($v) ? $v : array_search($v, $this->section);
			if (!is_null($old) && $old !== false && isset($this->section[$old])) {
				$new = $cur;
				$trans[$old] = $new;
				$section[$new] = $this->section[$old];
				$elements[$new] = $this->elements[$old];
				foreach(array_keys($elements[$new]) as $k)
					$elementsSection[$k] = $new;
				$cur++;
			}
		}
		if (count($section) < count($this->section)) {
			foreach($this->section as $old=>$v) {
				if (!isset($trans[$old])) {
					$new = $cur;
					$trans[$old] = $new;
					$section[$new] = $this->section[$old];
					$elements[$new] = $this->elements[$old];
					foreach(array_keys($elements[$new]) as $k)
						$elementsSection[$k] = $new;
					$cur++;
				}
			}
		}

		$this->section = $section;
		$this->elements = $elements;
		$this->elementsSection = $elementsSection;
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
			$ret = array_filter($ret, create_function('$v', 'return $v ? true : false;'));

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
				'name'=>str_replace('.', '_', $name),
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

		if (is_int($find) && $find < count($this->section))
			$this->curSection = $find;

		return $find;
	}
	
	/**
	 * Set a section name
	 *
	 * @param string $name Section name to set
	 * @param null|int|string $search null to set to the current section, int or string to search a section
	 * @return bool True if section name was correctly set
	 */
	public function setSectionName($name, $search = null) {
		$find = false;
		if (is_null($search)) {
			$find = $this->curSection;
		} else if (is_int($search)) {
			$find = $search;
		} else {
			foreach($this->section as $k=>&$s) {
				if ($s == $search)
					$find = $k;
			}
		}
		
		if (is_int($find) && $find < count($this->section)) {
			$this->section[$find] = $name;
			return true;
		}
		return false;
	}

	/**
	 * Move a field to another section
	 *
	 * @param string $name Fieldname
	 * @param int $section Section Number. If null, current section will be used
	 * @return bool True if the field was found and moved
	 */
	public function moveToSection($name, $section=null) {
		$f = $this->get($name);
		if ($f) {
			$curSection = $this->elementsSection[$name];
			$section = $section ? $section : $this->curSection;
			$this->elements[$section][$name] = $f;
			$this->elementsSection[$name] = $section;
			unset($this->elements[$curSection][$name]);
			return true;
		}
		return false;
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