<?php

namespace radium\extensions\helper;

use lithium\util\Inflector;
use lithium\core\Libraries;

class Form extends \lithium\template\helper\Form {

	/**
	 * String templates used by this helper.
	 *
	 * @var array
	 */
	protected $_strings = [
		'button'         => '<button{:options}>{:title}</button>',
		'checkbox'     	 => '<input type="checkbox" name="{:name}"{:options} />',
		'checkbox-label' => '<label class="control-label"><input type="checkbox" name="{:name}"{:options} />{:title}</label>',
		'checkbox-multi' => '<input type="checkbox" name="{:name}[]"{:options} />',
		'checkbox-multi-group' => '{:raw}',
		'error'          => '<span class="help-block">{:content}</span>',
		'annotation'     => '<small class="help-block annotation">{:content}</small>',
		'errors'         => '{:raw}',
		'input'          => '<input type="{:type}" name="{:name}"{:options} />',
		'file'           => '<input type="file" name="{:name}"{:options} />',
		'form'           => '<form action="{:url}"{:options}>{:append}',
		'form-end'       => '</form>',
		'hidden'         => '<input type="hidden" name="{:name}"{:options} />',
		'field'          => '<div{:wrap}>{:label}<div class="controls">{:annotation}{:input}{:error}</div></div>',
		'field-checkbox' => '<div{:wrap}>{:input}<div class="controls">{:label}{:error}</div></div>',
		'field-radio'    => '<div{:wrap}>{:input}<div class="controls">{:label}{:error}</div></div>',
		'label'          => '<label for="{:id}" class="control-label"{:options}>{:title}</label>',
		'legend'         => '<legend>{:content}</legend>',
		'option-group'   => '<optgroup label="{:label}"{:options}>{:raw}</optgroup>',
		'password'       => '<input type="password" name="{:name}"{:options} />',
		'radio'          => '<input type="radio" name="{:name}"{:options} />',
		'select'         => '<select name="{:name}"{:options}>{:raw}</select>',
		'select-empty'   => '<option value=""{:options}>&nbsp;</option>',
		'select-multi'   => '<select name="{:name}[]"{:options}>{:raw}</select>',
		'select-option'  => '<option value="{:value}"{:options}>{:title}</option>',
		'submit'         => '<input type="submit" value="{:title}"{:options} />',
		'submit-image'   => '<input type="image" src="{:url}"{:options} />',
		'text'           => '<input type="text" name="{:name}"{:options} />',
		'textarea'       => '<textarea name="{:name}"{:options}>{:value}</textarea>',
		'fieldset'       => '<fieldset{:options}><legend>{:content}</legend>{:raw}</fieldset>',

		'money'          => '<div class="input-prepend"><span class="add-on">$</span><input type="text" name="{:name}"{:options} /></div>',
		'date'           => '<input type="text" data-date-format="yyyy-mm-dd" class="date-field" name="{:name}"{:options} />',
		'submit-button'  => '<button type="submit"{:options}>{:name}</button>'
	];

	/**
	 * Generates an HTML `<input type="checkbox" />` object.
	 *
	 * @param string $name The name of the field.
	 * @param array $options Options to be used when generating the checkbox `<input />` element:
	 *        - `'checked'` _boolean_: Whether or not the field should be checked by default.
	 *        - `'value'` _mixed_: if specified, it will be used as the 'value' html
	 *          attribute and no hidden input field will be added.
	 *        - Any other options specified are rendered as HTML attributes of the element.
	 * @return string Returns a `<input />` tag with the given name and HTML attributes.
	 */
	public function checkbox($name, array $options = []) {
		$defaults = ['value' => '1', 'hidden' => true, 'label' => ''];
		$options += $defaults;
		$default = $options['value'];
		$key = $name;
		$out = '';

		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		list($scope, $options) = $this->_options($defaults, $options);

		if (!isset($options['checked'])) {
			$options['checked'] = ($this->binding($key)->data == $default);
		}
		if ($scope['hidden']) {
			$out = $this->hidden($name, ['value' => '', 'id' => false]);
		}
		$options['value'] = $scope['value'];
		if (!empty($scope['label'])) {
			$title = $scope['label'];
			$template = 'checkbox-label';
		}
		return $out . $this->_render(__METHOD__, $template, compact('name', 'options', 'title'));
	}

	public function button($title = null, array $options = []) {
		if (isset($options['icon'])) {
			$icon = $options['icon'];
			$title = sprintf('<i class="fa fa-%s"></i> %s', $icon, $title);
			$options['escape'] = false;
		}
		return parent::button($title, $options);
	}

	public function create($bindings = null, array $options = []) {
		$result = parent::create($bindings, $options);
		if ($this->_binding) {
			$model = $this->_binding->model();
			$this->model = $model;
			$this->instance = Libraries::instance('model', $model);
			$this->schema = $model::schema();
			$this->rules = $this->instance->validates;
		}
		return $result;
	}

	public function end() {
		unset($this->model);
		unset($this->instance);
		unset($this->schema);
		unset($this->rules);
		return parent::end();
	}

	public function field($name, array $options = []) {
		if (is_array($name)) {
			return $this->_fields($name, $options);
		}

		$method = __FUNCTION__;
		if (isset($options['type']) && !empty($this->_config['field-' . $options['type']])) {
			$method = 'field-' . $options['type'];
		}
		[$i, $options, $template] = $this->_defaults($method, $name, $options);


		$meta = (isset($this->schema) && is_object($this->schema))
			? $this->schema->fields($name)
			: [];

		$defaults = [
			'label' => null,
			'type' => isset($options['list']) ? 'select' : 'text',
			'template' => $template,
			'wrap' => ['class' => 'form-group'],
			'annotation' => null,
			'list' => null,
			'error' => []
		];
		if (!empty($meta['required'])) {
			$defaults['required'] = true;
		}
		[$options, $field] = $this->_options($defaults, $options);

		if ($this->_binding) {
			$errors = $this->_binding->errors();
			if (isset($errors[$name])) {
				$options['wrap']['class'] .= ' has-warning';
			}
		}

		# Auto-populate select-box lists from validation rules or methods
		if ($options['type'] == 'select' && empty($options['list'])) {
			$options = $this->_autoSelects($name, $options);
		}

		$label = $input = null;
		$wrap = $options['wrap'];
		$type = $options['type'];
		$list = $options['list'];
		$annotation = $options['annotation'];
		$error = $options['error'];
		$template = $options['template'];
		$notText = $template === 'field' && $type !== 'text';

		if ($notText && $this->_context->strings('field-' . $type)) {
			$template = 'field-' . $type;
		}
		if (($options['label'] === null || $options['label']) && $options['type'] !== 'hidden') {
			if (!$options['label']) {
				$options['label'] = Inflector::humanize(preg_replace('/[\[\]\.]/', '_', $name));
			}
			$label = $this->label(isset($options['id']) ? $options['id'] : '', $options['label']);
		}

		if ($type === 'text' && $list) {
			if (is_array($list)) {
				list($list, $datalist) = $this->_datalist($list, $options);
			}
			$field['list'] = $list;
		} else {
			$datalist = null;
		}

		$call = ($type === 'select') ? [$name, $list, $field] : [$name, $field];
		$input = call_user_func_array([$this, $type], $call);
		
		if ($error !== false && $this->_binding) {
			$error = $this->error($name, null, ['messages' => $error]);
		} else {
			$error = null;
		}
		if ($annotation) {
			$annotation = $this->_render(__METHOD__, 'annotation', ['content' => $annotation]);
		}
		return $this->_render(__METHOD__, $template, compact(
			'wrap', 'label', 'input', 'datalist', 'error', 'annotation'
		));
	}

	public function select($name, $list = [], array $options = []) {
		$defaults = ['multiple' => false, 'hidden' => true];
		$options += $defaults;
		$out = parent::select($name, $list, $options);
		if($options['multiple'] && $options['hidden']) {
			return $this->hidden($name.'[]', ['value' => '', 'id' => false]) . $out;
		}
		return $out;
	}

	protected function _autoSelects($name, array $options = []) {
		$model = $this->_binding->model();
		$method = Inflector::pluralize($name);
		$rules = $this->instance->validates;

		if (method_exists($model, $method)) {
			$list = $model::$method();
			if (!empty($list)) {
				$options['list'] = $list;
				return $options;
			}
		}

		if (isset($rules[$name])) {
			if (is_array($rules[$name][0])) {
				$rule_list = $rules[$name];
			} else {
				$rule_list = [$rules[$name]];
			}

			foreach ($rule_list as $rule) {
				if ($rule[0] === 'inList' and isset($rule['list'])) {
					foreach ($rule['list'] as $optval) {
						$options['list'][$optval] = Inflector::humanize($optval);
					}
				}
			}
		}
		return $options;
	}
}