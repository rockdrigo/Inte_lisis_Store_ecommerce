{% macro startForm(options) %}
	{% if options.type == 'vertical' %}
		<div class="verticalFormContainer {{ options.class }}">
	{% else %}
		<div class="horizontalFormContainer {{ options.class }}">
	{% endif %}
{% endmacro %}

{% macro endForm() %}
	</div>
{% endmacro %}

{% macro startHeading() %}
	<div class="header">
{% endmacro %}

{% macro endHeading() %}
	</div>
{% endmacro %}

{% macro heading(value) %}
	{% import "macros/forms.tpl" as formBuilder %}
	{{ formBuilder.startHeading }}
		{{ value }}
	{{ formBuilder.endHeading }}
{% endmacro %}

{% macro intro(value) %}
	<div class="intro">
		{{ value|safe }}
	</div>
{% endmacro %}

{% macro startRowGroup(options) %}
	<div class="formGroup {{ options.class }}" {% if options.id %}id="{{ options.id }}"{%endif %} style="{% if options.hidden %}display: none;{% endif %}">
{% endmacro %}

{% macro endRowGroup() %}
	</div>
{% endmacro %}

{% macro startRow(options) %}
	<div class="formRow {% if options.label == false %}formRowUnlabeled{% endif %} {{ options.class }} {% if options.last %}formRowLast{% endif %}" style="{% if options.hidden %}display: none;{% endif %}" {% if options.id %}id="{{ options.id }}"{% endif %}>
		{% if options.label %}
			<label class="label" {% if options.for %}for="{{ options.for }}"{% endif %}>
				<span class="Required" {% if not options.required %}style="visibility:hidden;"{% endif %}>*</span>
				{{ options.label }}
			</label>
		{% endif %}
		<div class="value">
{% endmacro %}

{% macro nodeJoin() %}
	<img src="images/nodejoin.gif" width="20" height="20" />
{% endmacro %}

{% macro endRow(note) %}
		{% if note %}
			<p class="note">{{ note }}</p>
		{% endif %}
	</div>
</div>
{% endmacro %}

{% macro select(name, options, selected, attributes) %}
	<select name="{{ name }}" id="{{ name }}" {% for name, value in attributes %} {{ name }}="{{ value }}" {% endfor %}>
		{% for value, label in options %}
			<option value="{{ value }}" {% if value == selected or value|in(selected) %}selected="selected"{% endif %}>{{ label }}</option>
		{% endfor %}
	</select>
{% endmacro %}

{% macro input(type, name, value, attributes) %}
	<input name="{{ name }}" type="{{ type }}" value="{{ value }}"
		{% for name, value in attributes %} {{ name }}="{{ value }}" {% endfor %}
	/>
{% endmacro %}

{% macro textarea(name, contents, attributes) %}
	<textarea name="{{ name }}" {% for name, value in attributes %} {{ name }}="{{ value }}" {% endfor %}>{{ contents }}</textarea>
{% endmacro %}

{% macro radioList(name, options, checked, attributes) %}
	{% for value, label in options %}
		<label>	<input name="{{ name }}" type="radio" value="{{ value }}"
				{% for name, value in attributes %}	{{ name }}="{{ value }}" {% endfor %}
				{% if checked == value %}checked="checked"{% endif %}
		/>{{ label }}</label>
		{% if loop.last == false %}<br />{% endif %}
	{% endfor %}
{% endmacro %}

{% macro startButtonRow(class) %}
	<p class="buttonRow {{ class }}">
{% endmacro %}

{% macro endButtonRow() %}
	</p>
{% endmacro %}

{% macro multiSelect(name, options, selected, attributes) %}
	<select name="{{ name }}" multiple="multiple" {% for name, value in attributes %} {{ name }}="{{ value }}" {% endfor %}>
		{% for value, label in options %}
			<option value="{{ value }}" {% if value|in(selected) %}selected="selected"{% endif %}>{{ label }}</option>
		{% endfor %}
	</select>
{% endmacro %}

{% macro hiddenInputs (pairs, exclude) %}
	{% for key, value in pairs %}
		{% if not key|in(exclude) %}
			<input type="hidden" name="{{ key }}" value="{{ value }}" />
		{% endif %}
	{% endfor %}
{% endmacro %}

{% macro saveButton(label, id) %}
	<input type="submit" id="{{ id }}" class="saveButton" value="{% lang 'Save' %}" />
{% endmacro %}

{% macro cancelButton(label, id) %}
	<input type="reset" id="{{ id }}" class="cancelButton" value="{% lang 'Cancel' %}" />
{% endmacro %}

{% macro checkbox(options) %}
	<label><input type="checkbox" name="{{ options.name }}" class="{{ options.class }}" {% if (options.id) %}id="{{ options.id }}"{% endif %} value="{{ options.value|default(1)|e }}" {% if (options.checked) %}checked="checked"{% endif %} /> {{ options.label }}</label>
{% endmacro %}
