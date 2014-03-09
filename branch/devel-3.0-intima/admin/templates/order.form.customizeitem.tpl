{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}
{% import "order.form.customizeitem.tpl" as self %}

<div class="ModalTitle">
	{% lang 'CustomizeItem' with [
		'item': item.name
	] %}
</div>
<form id="orderCustomizeItemForm" action="#">
	<div class="ModalContent orderCustomizeItemModal">
		<div id="orderCustomizeItem">
			<input type="hidden" name="activeTab" value="{{ item.activeTab }}" />
			<input type="hidden" name="productId" value="{{ item.productId }}" />
			<input type="hidden" name="itemId" value="{{ item.itemId }}" />
			<input type="hidden" name="quoteSession" value="{{ item.quoteSession }}" />

			<input type="hidden" name="variationId" value="{{ item.variationId }}" class="CartVariationId" />
			<input type="hidden" name="optionRequired" class="ProductVariationRequired" value="{{ product.prodoptionsrequired }}" />

			<input type="hidden" name="isCustomPrice" value="" />

			<ul class="tabnav">
				<li><a href="#basicTab">{% lang 'BasicDetails' %}</a></li>
				{% if variationOptions %}
					<li><a href="#variationTab">{% lang 'Variation' %}</a></li>
				{% endif %}
				{% if configurableFields %}
					<li><a href="#configurableFieldsTab">{% lang 'ConfigurableFields' %}</a></li>
				{% endif %}
				{% if product.prodeventdaterequired %}
					<li><a href="#eventDateTab">{% lang 'EventDate' %}</a></li>
				{% endif %}
				{% if giftWrappingOptions %}
					<li><a href="#wrappingTab">{% lang 'GiftWrapping' %}</a></li>
				{% endif %}
			</ul>

			<div id="basicTab">
				{{ formBuilder.startForm }}
					{{ formBuilder.heading(lang.BasicDetails) }}

					{{ formBuilder.startRow([
						'label': lang.Name ~ ':',
						'required': true
					]) }}
						<input type="text" name="name" value="{{ item.name }}" class="Field250" {% if item.productId %}disabled="disabled"{% endif %} />
					{{ formBuilder.endRow }}

					{% if not item.productId %}
						{{ formBuilder.startRow([
							'label': lang.SKU ~ ':',
						]) }}
							<input type="text" name="sku" value="{{ item.sku }}" class="Field100" />
						{{ formBuilder.endRow }}
					{% endif %}

					{{ formBuilder.startRow([
						'label': lang.ItemPrice ~ ':',
						'required': true
					]) }}
						{{ CurrencyTokenLeft }}
						<input type="text" name="price" value="{{ item.price }}" class="VariationProductPrice Field50" />
						{{ CurrencyTokenRight }}
					{{ formBuilder.endRow }}

					{{ formBuilder.startRow([
						'label': lang.Quantity ~ ':',
						'required': true
					]) }}
						<input type="text" name="quantity" value="{{ item.quantity }}" class="Field20" />
					{{ formBuilder.endRow }}
				{{ formBuilder.endForm }}
			</div>

			{% if variationOptions %}
				<div id="variationTab">
					{{ formBuilder.startForm }}
						{{ formBuilder.heading('Variation') }}

						{% for name in variationOptions %}
							{{ formBuilder.startRow([
								'label': name ~ ':',
								'required': product.prodoptionsrequired
							]) }}
								<select class="Field300 VariationSelect">
									<option value="">{{ lang.ChooseAnOption }}</option>
									{% for id, value in variationValues[name] %}
										<option value="{{ id }}" {% if item.variationOptions[name] == value %}selected="selected"{% endif %}>{{ value }}</option>
									{% endfor %}
								</select>
							{{ formBuilder.endRow }}
						{% endfor %}
					{{ formBuilder.endForm }}
				</div>
			{% endif %}

			{% if configurableFields %}
				<div id="configurableFieldsTab">
					{{ formBuilder.startForm }}
						{{ formBuilder.heading(lang.ConfigurableFields) }}

						{% for field in configurableFields %}
							{{ formBuilder.startRow([
								'label': field.name ~ ':',
								'required': field.required,
								'class': 'ConfigurableField'
							]) }}
								{% if field.type == "textarea" %}
									<textarea cols="30" rows="3" name="configurableFields[{{ field.id }}]" class="Field300 {% if field.required %}FieldRequired{% endif %}">{{ item.configuration[field.id].value }}</textarea>
								{% elseif field.type == "file" %}
									{% if item.configuration[field.id].value %}
										<div style="padding: 4px 0;">
											{% lang 'Currently' %}: <a href="#" target="_blank">{{ item.configuration[field.id].fileOriginalName }}</a>
											&nbsp;&nbsp;
											{% if field.required == 0 %}
											 <label>(<input type="checkbox" class="RemoveCheckbox" name="removeConfigurableField[{{ field.id }}]" value="1" />{{ lang.Remove}}?)</label>
											{% endif %}
										</div>
									{% endif %}
									<input type="file" name="configurableFields[{{ field.id }}]" class="Field300 {% if field.required %}FieldRequired{% endif %} {% if item.configuration[field.id].value %}HasExistingValue{% endif %}" />
									<div style="padding: 4px 0">
										<em>
											{% if field.fileSize > 0 %}
												{{ lang.MaximumSize }}: {{ (field.fileSize * 1024)|niceSize }}.
											{% endif %}
											{% if field.fileType %}
												{{ lang.AllowedTypes }}: <span cass="FileTypes">{{ field.fileType|upper }}</span>.
											{% endif %}
										</em>
									</div>
								{% elseif field.type == "select" %}
									<select name="configurableFields[{{ field.id }}]" class="Field300 {% if field.required %}FieldRequired{% endif %}">
										<option value="">{% lang 'FormFieldSetupChoosePrefixDefault' %}</option>
										{% for option in field.selectOptions %}
											<option value="{{ option }}" {% if option == item.configuration[field.id].value %}selected="selected"{% endif %}>{{ option }}</option>
										{% endfor %}
									</select>
								{% elseif field.type == "checkbox" %}
									<label>
										<input type="checkbox" name="configurableFields[{{ field.id }}]" value="1" {% if item.configuration[field.id].value %}checked="checked"{% endif %} class="{% if field.required %}FieldRequired{% endif %}" />
								{% else %}
									<input type="text" name="configurableFields[{{ field.id }}]" class="Field300 {% if field.required %}FieldRequired{% endif %}" value="{{ item.configuration[field.id].value }}" />
								{% endif %}
							{{ formBuilder.endRow }}
						{% endfor %}
					{{ formBuilder.endForm }}
				</div>
			{% endif %}

			{% if giftWrappingOptions %}
				<div id="wrappingTab">
					<p class="intro">{{ lang.ChooseHowToWrapItems }}</p>

					{{ formBuilder.startForm }}
						{{ formBuilder.heading(lang.GiftWrapping) }}

						{{ formBuilder.startRow([
							'label': lang.GiftWrappingMethod ~ ':',
							'required': true
						])}}
							<label class="row">
								<input type="radio" name="giftWrappingType" value="none" {% if item.wrapping.wrapid == 0 %}checked="checked"{% endif %} /> {% lang 'DoNotApplyGiftWrapping' %}
							</label>

							<label class="row">
								<input type="radio" name="giftWrappingType" value="same" {% if item.wrapping.wrapid %}checked="checked"{% endif %} />
								{% if item.quantity == 1 %} {% lang 'GiftWrapThisItem' %}
								{% else %} {{ lang.WrapItemsTheSame }}
								{% endif %}
							</label>

							{% if item.quantity > 1 %}
								<label class="row">
									<input type="radio" name="giftWrappingType" value="different" /> {{ lang.WrapItemsDifferently }}
								</label>
							{% endif %}
						{{ formBuilder.endRow }}

						{{ formBuilder.startRowGroup([
							'hidden': (item.wrapping.wrapid == 0),
							'class': 'giftWrappingTypeSame giftWrappingType'
						]) }}
							{{ self.drawGiftWrappingOptions(lang, giftWrappingOptions, item.wrapping, 'all') }}
						{{ formBuilder.endRowGroup }}
					{{ formBuilder.endForm }}

					{% if item.quantity > 1 %}
						<div class="giftWrappingType giftWrappingTypeDifferent" style="display: none">
							{% for num in 1..item.quantity %}
								{{ formBuilder.startForm }}
									{% set heading %}{% lang 'GiftWrappingForOne' with ['item': item.name] %}{% endset %}
									{{ formBuilder.heading(heading) }}

									{{ self.drawGiftWrappingOptions(lang, giftWrappingOptions, item.wrapping, num) }}
								{{ formBuilder.endForm }}
							{% endfor %}
						</div>
					{% endif %}
				</div>
			{% endif %}

			{% if product.prodeventdaterequired %}
				<div id="eventDateTab">
					{{ formBuilder.startForm }}
						{{ formBuilder.heading(lang.EventDate) }}

						{{ formBuilder.startRow([
							'label': product.prodeventdatefieldname~ ':',
							'required': true
						]) }}
							<select name="eventDate[month]" class="Field50" id="EventDateMonth">
								<option value="-1">---</option>
								{% for id, month in eventDate.monthOptions %}
									<option value="{{ id }}" {% if id == item.eventDate.month %}selected="selected"{% endif %}>{{ month }}</option>
								{% endfor %}
							</select>
							<select name="eventDate[day]" class="Field50" id="EventDateDay">
								<option value="-1">---</option>
								{% for day in 1..31 %}
									<option value="{{ day }}" {% if day == item.eventDate.day %}selected="selected"{% endif %}>{{ day }}</option>
								{% endfor %}
							</select>
							<select name="eventDate[year]" class="Field75" id="EventDateYear">
								<option value="-1">---</option>
								{% for value in eventDate.yearFrom..eventDate.yearTo %}
									<option value="{{ value }}" {% if value == item.eventDate.year %}selected="selected"{% endif %}>{{ value }}</option>
								{% endfor %}
							</select>

							<div class="note">
								{% if eventDate.limitationType %}
									{% lang 'EventDateLimitations' ~ eventDate.limitationType with [
										'from': eventDate.fromStamp|date,
										'to': eventDate.toStamp|date
									] %}
								{% endif %}
							</div>
						{{ formBuilder.endRow }}
					{{ formBuilder.endForm }}
				</div>
			{% endif %}
		</div>
	</div>
	<div class="ModalButtonRow">
		<div class="FloatLeft">
			<input class="CloseButton" type="button" value="{% lang 'Close' %}" onclick="$.modal.close();" />
		</div>
		<input type="submit" class="SubmitButton" value="{% if item.itemId %}{% lang 'UpdateItem' %}{% else %}{% lang 'AddItem' %}{% endif %}" />
	</div>
</form>
<script type="text/javascript" charset="utf-8">
	Order_Form.customizeItemModalLoaded();
	var eventDateData = {
		type: '{{ eventDate.limitationType }}',
		compDate: '{{ eventDate.compDate }}',
		compDateEnd: '{{ eventDate.compDateEnd }}',
		invalidMessage: '{% jslang 'EventDateInvalid' with [
			'name': product.prodeventdatefieldname
		] %}',
		errorMessage: '{% jslang 'EventDateLimitationsLong' ~ eventDate.limitationType with [
			'name': product.prodeventdatefieldname,
			'from': eventDate.fromStamp|date,
			'to': eventDate.toStamp|date
		] %}'
	};
</script>

{% macro drawGiftWrappingOptions(lang, giftWrappingOptions, selectedWrapping, giftWrappingId) %}
	{% import "macros/forms.tpl" as formBuilder %}

	{{ formBuilder.startRowGroup([
		'class': 'giftWrappingOptionGroup'
	]) }}
		{{ formBuilder.startRow([
			'label': lang.GiftWrapping ~ ':',
			'required': true
		]) }}
			<select class="Field300 giftWrappingSelect" name="giftWrapping[{{ giftWrappingId }}]" id="giftWrapping_{{ giftWrappingId }}">
				<option value="">{{ lang.ChooseGiftWrappingOption }}</option>
				{% for option in giftWrappingOptions %}
					<option value="{{ option.wrapid }}" {% if selectedWrapping.wrapid == option.wrapid %}selected="selected"{% endif %} class="{% if option.wrappreview %}hasPreview{% endif %} {% if option.wrapallowcomments %}allowComments{% endif %}">
						{{ option.wrapname }} ({{option.wrapprice|formatPrice }})
					</option>
				{% endfor %}
			</select>
			<span>
				&nbsp;
				{% for option in giftWrappingOptions %}
					{% if option.wrappreview %}
						<a class="giftWrappingPreviewLink{{ option.wrapid }} giftWrappingPreviewLink" target="_blank" href="../{{ option.wrappreview }}" style="display: none">
							{{ lang.Preview }}
						</a>
					{% endif %}
				{% endfor %}
			</span>
		{{ formBuilder.endRow }}

		{{ formBuilder.startRow([
			'label': lang.GiftMessage ~ ':',
			'class': 'giftMessage',
			'hidden': (selectedWrapping.wrapid == 0)
		]) }}
			<textarea class="Field300" rows="5" name="giftMessage[{{ giftWrappingId }}]" id="giftMessage_{{ giftWrappingId }}">{{ selectedWrapping.wrapmessage }}</textarea>
		{{ formBuilder.endRow }}
	{{ formBuilder.endRowGroup }}
{% endmacro %}
