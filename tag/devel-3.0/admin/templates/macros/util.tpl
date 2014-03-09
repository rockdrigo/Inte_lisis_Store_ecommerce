{% macro paging(numResults, perPage, currentPage, url, showPerPage) %}
	{% if '?'|in(url) == false %}{% set url = url ~ '?' %}{% endif %}
	{% set numPagesFloat = (numResults / perPage) %}
	{% set numPages = (numResults // perPage) %}
	{% if numPagesFloat > numPages %}
		{% set numPages = (numPages + 1) %}
	{% endif %}
	{% set start = (currentPage - 4) %}
	{% set end = (currentPage + 4) %}
	{%if start < 1 %}{% set start = 1 %}{% endif %}
	{%if end > numPages %}{% set end = numPages %}{% endif %}
	{% if numResults > 0 %}
		<p class="paging">
			({% lang 'Pagination' with [
				'page': currentPage,
				'num_pages': numPages
			] %})
			&nbsp;&nbsp;
			{% if currentPage > 1 %}
				<a href="{{ url }}&amp;page=1">&laquo;&laquo;</a> |
				<a href="{{ url }}&amp;page={{ currentPage - 1 }}">&laquo; {% lang 'Previous' %}</a> |
			{% else %}
				&laquo;&laquo; | &laquo; {% lang 'Previous' %} |
			{% endif %}
			{% for i in start..end %}
				{% if i == currentPage %}<strong>{{ i }}</strong>&nbsp;|&nbsp;
				{% else %}<a href="{{ url }}&amp;page={{ i }}">{{ i }}</a>&nbsp;|&nbsp;
				{% endif %}
			{% endfor %}
			{% if currentPage != numPages %}
				<a href="{{ url }}&amp;page={{ currentPage + 1 }}">{% lang 'Next' %} &raquo;</a> |
				<a href="{{ url }}&amp;page={{ numPages }}">&raquo;&raquo;</a>
			{% else %}
				{% lang 'Next' %} &raquo; | &raquo;&raquo;
			{% endif %}
			{% if showPerPage %}
				{% set pages = [5, 10, 20, 30, 50, 100] %}
				&nbsp;
				<select class="PerPage">
				{% for page in pages %}
					<option {% if page == perPage %}selected="selected"{% endif %} value="{{ page }}">{% lang 'PerPageX' with ['count':page] %}</option>
				{% endfor %}
				</select>
			{% endif %}
		</p>
	{% endif %}
{% endmacro %}

{% macro tooltip(title, content, replacements) %}
	<div class="tooltip">
		<div class="tooltipContent">
			<p class="title">{% lang title with replacements %}</p>
			<p class="message">
				{% lang content with replacements %}
			</p>
		</div>
	</div>
{% endmacro %}

{% macro tabs(tabs) %}
	<ul class="tabnav">
		{% for id, label in tabs %}
			<li><a href="#{{ id }}"><span>{{ label }}</span></a></li>
		{% endfor %}
	</ul>
{% endmacro %}

{% macro startDropDownMenu(options) %}
	<div id="{{ options.id }}" class="DropShadow DropDownMenu" style="display:none; width:{{ options.width|default('200px')|e }};">
{% endmacro %}

{% macro endDropDownMenu() %}
	</div>
{% endmacro %}

{% macro startDropDownMenuItemGroup() %}
	<ul>
{% endmacro %}

{% macro endDropDownMenuItemGroup() %}
	</ul>
{% endmacro %}

{% macro dropDownMenuItem(options) %}
	<li {% if (options.id) %}id="{{ options.id }}"{% endif %} {% if options.class %}class="{{ options.class }}"{% endif %} style="{% if (options.display) %}display:{{ options.display }};{% endif %}">
		<a href="{{ options.href|default('javascript:;')|e }}" style="{% if (options.backgroundImage) %}background-image:url('{{ options.backgroundImage }}');padding-left:28px;{% endif %}">
			{{ options.label }}
		</a>
	</li>
{% endmacro %}

{% macro dropDownMenuGroupSeparator() %}
	<hr />
{% endmacro %}

{#
dropDownMenu usage example (see the individual macros above for exact options available):

	{{ util.dropDownMenu([
		'id': 'CustomerExportMenu',
		'groups': [
			[
				['backgroundImage': 'images/view_add.gif', 'label': 'foo'],
				['backgroundImage': 'images/view_add.gif', 'label': 'bar']
			],
			[
				['backgroundImage': 'images/view_add.gif', 'label': 'baz']
			]
		]
	]) }}

#}

{% macro dropDownMenu(options) %}
	{% import "macros/util.tpl" as util %}
	{{ util.startDropDownMenu(options) }}
		{% for group in options.groups %}
			{% if loop.index > 1 %}
				{{ util.dropDownMenuGroupSeparator() }}
			{% endif %}
			{{ util.startDropDownMenuItemGroup() }}
				{% for item in group %}
					{{ util.dropDownMenuItem(item) }}
				{% endfor %}
			{{ util.endDropDownMenuItemGroup() }}
		{% endfor %}
	{{ util.endDropDownMenu() }}
{% endmacro %}

{% macro address(address) %}
	{% if address.firstname or address.lastname %}
		<div>{{ address.firstname}} {{ address.lastname }}</div>
	{% else %}
		<div>{{ address.first_name}} {{ address.last_name }}</div>
	{% endif %}

	<div>{{ address.company }}</div>

	{% if address.address1 or address.address2 %}
		<div>{{ address.address1 }}</div>
		<div>{{ address.address2 }}</div>
	{% else %}
		<div>{{ address.address_1 }}</div>
		<div>{{ address.address_2 }}</div>
	{% endif %}

	<div>
		{% set state =  address.state|default(address.getStateName) %}
		{{ address.city }}{% if address.city and (state or address.zip) %}, {% endif %}
		{{ state }}{% if state and address.zip %}, {% endif %}{{ address.zip }}
	</div>
	<div>
		{{ address.country|default(address.getCountryName) }}

		{% if address.countryFlag %}
			<img src="../lib/flags/{{ address.countryFlag }}.gif" style="vertical-align: middle" alt="" />
		{% endif %}
	</div>
{% endmacro %}

{#
jslang outputs a list of language variables as lang.<var> = <text>; assignments

usage:

{{ util.jslang([
	'ChooseVariationBeforeAdding',
	'GiftWrappingForOne': ['item': 'foo'],
	...
]) }}
#}
{% macro jslang (list) %}
	{% for index, lang in list %}
		{% if lang|keys|length %}
			lang["{{ index|js }}"] = "{% jslang index with lang %}";
		{% else %}
			lang["{{ lang|js }}"] = "{% jslang lang %}";
		{% endif %}
	{% endfor %}
{% endmacro %}

{% macro enabledSwitch(isEnabled) %}
	{% if isEnabled %}
		<img border="0" alt="{% lang 'Tick' %}" src="images/tick.gif"/>
	{% else %}
		<img border="0" alt="{% lang 'Cross' %}" src="images/cross.gif"/>
	{% endif %}
{% endmacro %}
