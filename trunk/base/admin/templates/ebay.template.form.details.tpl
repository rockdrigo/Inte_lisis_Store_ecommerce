{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as form %}
{% import "macros/ebay.tpl" as ebay %}

<ul class="tabnav" id="mainTabs">
	<li><a href="#" id="generalTab">{% lang 'GeneralTab' %}</a></li>
	<li><a href="#" id="paymentTab">{% lang 'PaymentTab' %}</a></li>
	<li><a href="#" id="shippingTab">{% lang 'ShippingTab' %}</a></li>
	<li><a href="#" id="otherTab">{% lang 'OtherTab' %}</a></li>
</ul>

<!-- General Tab -->
<div class="mainContent" id="generalTabContent" style="display: none;">
	<!-- Generic Item Settings -->
	{{ form.startForm }}
		{{ form.heading(lang.ItemDetails) }}

		{{ form.startRow([ 'label': lang.QuantityToSell, 'required': true ]) }}
			<label><input type="radio" value="one" id="quantiyTypeOne" name="quantityType" {% if quantityOption == 'one' %}checked="checked"{% endif %} />{{ lang.JustOne }}</label>
			<br />

			<label><input type="radio" value="more" id="quantiyTypeMore" name="quantityType" {% if quantityOption == 'more' %}checked="checked"{% endif %} />{{ lang.MoreThanOne }}</label>
			<div class="NodeJoin" {% if quantityOption != 'more' %}style="display: none;"{% endif %}>
				<img src="images/nodejoin.gif" alt="" />
				<input type="text" class="Field20" id="quantityMore" name="quantityMore" value="{{ moreQuantity }}" />

				<div style="padding-top:10px">
					{{ lang.SellMoreRequirementsDesc }}:
					<ul>
						<li>{{ lang.SellMoreRequirement1|safe }}</li>
						<li>{{ lang.SellMoreRequirement2|safe }}</li>
						<li>{{ lang.SellMoreRequirement3|safe }}</li>
					</ul>
				</div>
			</div>
		{{ form.endRow }}

		{{ form.startRow([ 'label': lang.ItemPhoto ]) }}
			<label><input type="checkbox" value="1" id="useItemPhoto" name="useItemPhoto" {% if useItemPhoto %}checked="checked"{% endif %} />{{ lang.YesUseItemPhoto }}</label>
		{{ form.endRow }}

	{{ form.endForm }}

	<!-- Item Location -->
	{{ form.startForm }}
		{{ form.heading(lang.ItemLocationDetails) }}

		{{ form.startRow([ 'label': lang.CountryDetails, 'required': true ]) }}
			{{ form.select('locationCountry', countries, locationCountry) }}
		{{ form.endRow }}

		{{ form.startRow([ 'label': lang.CityState, 'required': true ]) }}
			<input type="text" name="locationCityState" id="locationCityState" class="Field250" value="{{ locationCityState }}" />
		{{ form.endRow }}

		{{ form.startRow([ 'label': lang.ZipPostcodeDetails, 'required': true ]) }}
			<input type="text" name="locationZip" id="locationZip" class="Field50" value="{{ locationZip }}"/>
			{{ util.tooltip('ZipPostcodeDetailsHelpTitle', 'ZipPostcodeDetailsHelp') }}
		{{ form.endRow }}
	{{ form.endForm }}

	<!-- Selling Method -->
	{{ form.startForm }}
		{{ form.heading(lang.SellingMethodHeading) }}

		{{ form.startRow([ 'label': lang.SellingMethod, 'required': true ]) }}
			{{ form.radioList('sellingMethod', ['Chinese': lang.OnlineAuction, 'FixedPriceItem': lang.FixedPrice], sellingMethod) }}
		{{ form.endRow }}
	{{ form.endForm }}

	<!-- Auction Method -->
	<div class="sellingMethodContent" id="auctionTabContent" style="display: none;">
		{{ form.startForm }}

			{{ form.heading(lang.OnlineAuctionSettings) }}

			{% if not options.reserve_price_allowed %}
				<p class="MessageBox MessageBoxInfo">{{ lang.ReservePriceNotAllowed }}</p>
			{% endif %}

			<!-- Reserve Price -->
			{% if options.reserve_price_allowed %}

				{{ form.startRow([ 'label': lang.ReservePrice ]) }}
					<label><input type="checkbox" value="1" name="useReservePrice" id="useReservePrice" {% if useReservePrice %}checked="checked"{% endif %} />{{ lang.YesReservePriceText }}</label>
					{{ util.tooltip('ReservePriceHelpTitle', 'ReservePriceHelp') }}
					<div class="NodeJoin" {% if useReservePrice == false %}style="display: none;"{% endif %}>
						<div>
							<img src="images/nodejoin.gif" alt="" />
							<label><input type="radio" value="ProductPrice" id="reservePriceProduct" name="reservePriceOption" {% if reservePriceOption == 'ProductPrice' %}checked="checked"{% endif %} />{{ lang.UseProductPrice }}</label>
							{{ util.tooltip('UseProductPrice', 'UseProductPriceHelp') }}
						</div>
						<div>
							<img src="images/nodejoin.gif" alt="" />
							<label><input type="radio" value="PriceExtra" id="reservePriceProductPlus" name="reservePriceOption" {% if reservePriceOption == 'PriceExtra' %}checked="checked"{% endif %}/>{{ lang.UseProductPrice }}</label>:
							<select id="reservePricePlusOperator" name="reservePricePlusOperator" class="Field50">
								<option value="plus" {% if reservePriceCalcOperator == 'plus' %}selected="selected"{% endif %}>plus</option>
								<option value="minus" {% if reservePriceCalcOperator == 'minus' %}selected="selected"{% endif %}>minus</option>
							</select>
							<input type="text" name="reservePricePlusValue" id="reservePricePlusValue" class="Field40" value="{{ ebay.formatPriceOrPercent(reservePriceCalcPrice, reservePriceCalcOption, currency) }}" />
							<select id="reservePricePlusType" name="reservePricePlusType">
								<option value="percent" {% if reservePriceCalcOption == 'percent' %}selected="selected"{% endif %}>%</option>
								<option value="amount" {% if reservePriceCalcOption == 'amount' %}selected="selected"{% endif %}>{{ currencyToken }}</option>
							</select>
							{{ util.tooltip('UseProductPricePlus', 'UseProductPricePlusHelp') }}
						</div>
						<div>
							<img src="images/nodejoin.gif" alt="" />
							<label><input type="radio" value="CustomPrice" id="reservePriceCustom" name="reservePriceOption" {% if reservePriceOption == 'CustomPrice' %}checked="checked"{% endif %} />{{ lang.UseThisPrice }}</label>:
							{{ currencyToken }} <input type="text" name="reservePriceCustomValue" id="reservePriceCustomValue" class="Field50" value="{{ reservePriceCustom|formatPrice(false, false, false, currency) }}" />
							{{ util.tooltip('UseThisPrice', 'UseThisPriceHelp') }}
						</div>
					</div>
				{{ form.endRow }}

			{% endif %}

			<!-- Start Price -->
			{{ form.startRow([ 'label': lang.StartingPrice, 'required': true ]) }}
				<label>{{ lang.StartingPriceOption }}</label>
				{{ util.tooltip('StartingPriceHelpTitle', 'StartingPriceHelp') }}
				<div class="NodeJoin">
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input type="radio" value="ProductPrice" id="startPriceProduct" name="startPriceOption" {% if startPriceOption == 'ProductPrice' %}checked="checked"{% endif %} />{{ lang.UseProductPrice }}</label>
						{{ util.tooltip('UseProductPrice', 'UseProductPriceHelp') }}
					</div>
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input type="radio" value="PriceExtra" id="startPriceProductPlus" name="startPriceOption" {% if startPriceOption == 'PriceExtra' %}checked="checked"{% endif %}/>{{ lang.UseProductPrice }}</label>:
						<select id="startPricePlusOperator" name="startPricePlusOperator" class="Field50">
							<option value="plus" {% if startPriceCalcOperator == 'plus' %}selected="selected"{% endif %}>plus</option>
							<option value="minus" {% if startPriceCalcOperator == 'minus' %}selected="selected"{% endif %}>minus</option>
						</select>
						<input type="text" name="startPricePlusValue" id="startPricePlusValue" class="Field40" value="{{ ebay.formatPriceOrPercent(startPriceCalcPrice, startPriceCalcOption, currency) }}" />
						<select id="startPricePlusType" name="startPricePlusType">
							<option value="percent" {% if startPriceCalcOption == 'percent' %}selected="selected"{% endif %}>%</option>
							<option value="amount" {% if startPriceCalcOption == 'amount' %}selected="selected"{% endif %}>{{ currencyToken }}</option>
						</select>
						{{ util.tooltip('UseProductPricePlus', 'UseProductPricePlusHelp') }}
					</div>
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input type="radio" value="CustomPrice" id="startPriceCustom" name="startPriceOption" {% if startPriceOption == 'CustomPrice' %}checked="checked"{% endif %}/>{{ lang.UseThisPrice }}</label>:
						{{ currencyToken }} <input type="text" name="startPriceCustomValue" id="startPriceCustomValue" class="Field50" value="{{ startPriceCustom|formatPrice(false, false, false, currency) }}" />
						{{ util.tooltip('UseThisPrice', 'UseThisPriceHelp') }}
					</div>
				</div>
			{{ form.endRow }}

			<!-- Buy It Now Price -->
			{{ form.startRow([ 'label': lang.BuyItNowPrice ]) }}
				<label><input type="checkbox" value="1" name="useBuyItNowPrice" id="useBuyItNowPrice" {% if useBuyItNowPrice %}checked="checked"{% endif %} />{{ lang.YesBuyItNowPriceText }}</label>
				{{ util.tooltip('BuyItNowPriceHelpTitle', 'BuyItNowPriceHelp') }}
				<div class="NodeJoin" {%if useBuyItNowPrice == false %}style="display: none;"{% endif %}>
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input type="radio" value="ProductPrice" id="buyItNowPriceProduct" name="buyItNowPriceOption" {% if buyItNowPriceOption == 'ProductPrice' %}checked="checked"{% endif %} />{{ lang.UseProductPrice }}</label>
						{{ util.tooltip('UseProductPrice', 'UseProductPriceHelp') }}
					</div>
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input type="radio" value="PriceExtra" id="buyItNowPriceProductPlus" name="buyItNowPriceOption" {% if buyItNowPriceOption == 'PriceExtra' %}checked="checked"{% endif %}/>{{ lang.UseProductPrice }}</label>:
						<select id="buyItNowPricePlusOperator" name="buyItNowPricePlusOperator" class="Field50">
							<option value="plus" {% if buyItNowPriceCalcOperator == 'plus' %}selected="selected"{% endif %}>plus</option>
							<option value="minus" {% if buyItNowPriceCalcOperator == 'minus' %}selected="selected"{% endif %}>minus</option>
						</select>
						<input type="text" name="buyItNowPricePlusValue" id="buyItNowPricePlusValue" class="Field40" value="{{ ebay.formatPriceOrPercent(buyItNowPriceCalcPrice, buyItNowPriceCalcOption, currency) }}" />
						<select id="buyItNowPricePlusType" name="buyItNowPricePlusType">
							<option value="percent" {% if buyItNowPriceCalcOption == 'percent' %}selected="selected"{% endif %}>%</option>
							<option value="amount" {% if buyItNowPriceCalcOption == 'amount' %}selected="selected"{% endif %}>{{ currencyToken }}</option>
						</select>
						{{ util.tooltip('UseProductPricePlus', 'UseProductPricePlusHelp') }}
					</div>
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input type="radio" value="CustomPrice" id="buyItNowPriceCustom" name="buyItNowPriceOption" {% if buyItNowPriceOption == 'CustomPrice' %}checked="checked"{% endif %}/>{{ lang.UseThisPrice }}</label>:
						{{ currencyToken }} <input type="text" name="buyItNowPriceCustomValue" id="buyItNowPriceCustomValue" class="Field50" value="{{ buyItNowPriceCustom|formatPrice(false, false, false, currency) }}" />
						{{ util.tooltip('UseThisPrice', 'UseThisPriceHelp') }}
					</div>
				</div>
			{{ form.endRow }}

			<!-- Listing duration -->
			{{ form.startRow([ 'label': lang.ListingDuration, 'required': true ]) }}
				{{ form.select('auctionDuration', auctionDurations, auctionDuration) }}
			{{ form.endRow }}

		{{ form.endForm }}
	</div>

	<!-- Fixed Price Method -->
	<div class="sellingMethodContent" id="fixedPriceTabContent" style="display: none;">
		{{ form.startForm }}

			{{ form.heading(lang.FixedPriceSettings) }}

			<!-- Buy It Now Price -->
			{{ form.startRow([ 'label': lang.BuyItNowPrice, 'required': true ]) }}
				<label>{{ lang.BuyItNowPriceOption }}</label>
				{{ util.tooltip('BuyItNowPriceHelpTitle', 'BuyItNowPriceHelp') }}
				<div class="NodeJoin">
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input type="radio" value="ProductPrice" id="fixedBuyItNowPriceProduct" name="fixedBuyItNowPriceOption" {% if fixedBuyItNowPriceOption == 'ProductPrice' %}checked="checked"{% endif %} />{{ lang.UseProductPrice }}</label>
						{{ util.tooltip('UseProductPrice', 'UseProductPriceHelp') }}
					</div>
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input type="radio" value="PriceExtra" id="fixedBuyItNowPriceProductPlus" name="fixedBuyItNowPriceOption" {% if fixedBuyItNowPriceOption == 'PriceExtra' %}checked="checked"{% endif %}/>{{ lang.UseProductPrice }}</label>:
						<select id="fixedBuyItNowPricePlusOperator" name="fixedBuyItNowPricePlusOperator" class="Field50">
							<option value="plus" {% if fixedBuyItNowPriceCalcOperator == 'plus' %}selected="selected"{% endif %}>plus</option>
							<option value="minus" {% if fixedBuyItNowPriceCalcOperator == 'minus' %}selected="selected"{% endif %}>minus</option>
						</select>
						<input type="text" name="fixedBuyItNowPricePlusValue" id="fixedBuyItNowPricePlusValue" class="Field40" value="{{ ebay.formatPriceOrPercent(fixedBuyItNowPriceCalcPrice, fixedBuyItNowPriceCalcOption, currency) }}" />
						<select id="fixedBuyItNowPricePlusType" name="fixedBuyItNowPricePlusType">
							<option value="percent" {% if fixedBuyItNowPriceCalcOption == 'percent' %}selected="selected"{% endif %}>%</option>
							<option value="amount" {% if fixedBuyItNowPriceCalcOption == 'amount' %}selected="selected"{% endif %}>{{ currencyToken }}</option>
						</select>
						{{ util.tooltip('UseProductPricePlus', 'UseProductPricePlusHelp') }}
					</div>
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input type="radio" value="CustomPrice" id="fixedBuyItNowPriceCustom" name="fixedBuyItNowPriceOption" {% if fixedBuyItNowPriceOption == 'CustomPrice' %}checked="checked"{% endif %} />{{ lang.UseThisPrice }}</label>:
						{{ currencyToken }} <input type="text" name="fixedBuyItNowPriceCustomValue" id="fixedBuyItNowPriceCustomValue" class="Field50" value="{{ fixedBuyItNowPriceCustom|formatPrice(false, false, false, currency) }}" />
						{{ util.tooltip('UseThisPrice', 'UseThisPriceHelp') }}
					</div>
				</div>
			{{ form.endRow }}

			<!-- Listing duration -->
			{{ form.startRow([ 'label': lang.ListingDuration, 'required': true ]) }}
				{{ form.select('fixedDuration', fixedDurations, fixedDuration) }}
			{{ form.endRow }}

		{{ form.endForm }}
	</div>
</div>

<!-- Payment Tab -->
<div class="mainContent" id="paymentTabContent" style="display: none;">
	{{ form.startForm }}

		{{ form.heading(lang.PaymentDetails) }}

		{% if options.paypal_required %}
			<p class="MessageBox MessageBoxInfo">{{ lang.EbayPayPalRequired|safe }}</p>
		{% endif %}

		<!-- Payment Methods -->
		{{ form.startRow([ 'label': lang.PaymentMethods, 'required': true ]) }}
			{% for paymentMethodCode, paymentMethodName in paymentMethods %}
				{% set methodChecked = false %}
				{% set isPayPalRequired = false %}

				{% if paymentMethodCode in selectedPaymentMethods %}
					{% set methodChecked = true %}
				{% endif %}

				{% if paymentMethodCode == 'PayPal' and options.paypal_required %}
					{% set methodChecked = true %}
					{% set isPayPalRequired = true %}
				{% endif %}
				<label>
					<input type="checkbox" name="paymentMethods[{{ loop.index0 }}]" id="paymentMethod_{{ paymentMethodCode }}" value="{{ paymentMethodCode }}" {% if methodChecked %}checked="checked"{% endif %} {% if isPayPalRequired %}disabled="disabled"{% endif %}/>
					{{ paymentMethodName }}
				</label>
				<br />
			{% endfor %}
		{{ form.endRow }}

		<!-- PayPal Email Address -->
		{% if 'PayPal' in paymentMethods %}
			{{ form.startRow([ 'label': lang.PaypalEmailAddress, 'required': options.paypal_required ]) }}
				<input type="text" name="paypalEmailAddress" id="paypalEmailAddress" class="Field200" value="{{ paypalEmailAddress }}"/>
				{{ util.tooltip('PaypalEmailHelpTitle', 'PaypalEmailHelp') }}
			{{ form.endRow }}
		{% endif %}

	{{ form.endForm }}
</div>

<!-- Shipping Tab -->
<div class="mainContent" id="shippingTabContent" style="display: none;">
	{{ form.startForm }}

		{{ form.heading(lang.ShippingDetails) }}

		<p class="intro">{{ lang.ShippingIntro }}</p>

		<p class="intro MessageBox MessageBoxInfo">{{ lang.ShippingDimensionsWarning|safe }}</p>

		<ul class="tabnav" id="shippingTabs">
			<li><a href="#" id="domesticTab">{{ lang.DomesticShipping }}</a></li>
			<li><a href="#" id="internationalTab">{{ lang.InternationalShipping }}</a></li>
		</ul>

		<!-- Domestic shipping -->
		<div class="shippingContent" id="domesticTabContent" style="display: none;">
			{{ form.startForm }}

				{{ form.startRow([ 'label': lang.ShippingMethods, 'required': options.domestic_shipping_required ]) }}
					<label><input type="radio" value="pickup" id="domesticShipping_pickup" name="domesticShipping" {% if useDomesticShipping == false %}checked="checked"{% endif %} />{{ lang.NoDomesticShipping }}</label>
					<br />
					<label><input type="radio" value="specify" id="domesticShipping_specify" name="domesticShipping" {% if useDomesticShipping %}checked="checked"{% endif %} />{{ lang.SpecifyDomesticShipping }}</label>
					<div class="NodeJoin" id="domesticShippingOptions" {% if useDomesticShipping == false %}style="display: none;"{% endif %}>
						<div>
							<img src="images/nodejoin.gif" alt=""/>
							{{ lang.ShippingCostType }}
							{{ form.select('domesticShippingType', domesticShippingCostTypes, domesticShippingCostType) }}
						</div>

						<!-- Flat rate options -->
						<div id="domesticServiceTypeFlat" style="margin-left: 20px;">
							<img src="images/nodejoin.gif" alt="" align="top"/>
							<table cellspacing="0" cellpadding="2" style="display: inline-table;" class="domestic Flat">
								<tr>
									<td style="width:307px;">
										{{ lang.ShippingServices }}
									</td>
									<td style="width:71px;">
										{{ lang.FirstItemCost }}
									</td>
									<td class="Field80">
										{{ lang.EachAdditional }}
									</td>
								</tr>
								{% if domesticShippingCostType == 'Flat' %}
									{% for currentServiceId, serviceOptions in domesticFlatShippingServices %}
										<tr>
											<td>
												<select name="domesticShippingServFlat[{{ loop.index0 }}][Type]" class="domesticShippingServFlatType Field300">
													<option value="">{{ lang.OptionSeparator }}</option>
													{% for carrier, services in DomesticShippingServFlat %}
														<optgroup label="{{ carrier }}">
															{% for serviceCode, service in services %}
																<option value="{{ serviceCode }}" {% if service.class %}class="{{ service.class }}"{% endif %} {% if serviceCode == currentServiceId %}selected="selected"{% endif %}>{{ service.name }}</option>
															{% endfor %}
														</optgroup>
													{% endfor %}
												</select>
											</td>
											<td>
												{{ currencyToken }} <input type="text" name="domesticShippingServFlat[{{ loop.index0 }}][Cost]" class="Field50 domesticShippingServFlatCost" value="{{ serviceOptions.cost|formatPrice(false, false, false, currency) }}" />
											</td>
											<td>
												{{ currencyToken }} <input type="text" name="domesticShippingServFlat[{{ loop.index0 }}][MoreCost]" class="Field50 domesticShippingServFlatMoreCost" value="{{ serviceOptions.additional_cost|formatPrice(false, false, false, currency) }}" />
											</td>
											<td>
												<img class="ShippingServiceAdd" src="images/addicon.gif" alt="{% jslang 'Add' %}" border="0" style="cursor:pointer;" />
												<img class="ShippingServiceRemove" src="images/delicon.gif" alt="{% jslang 'Remove' %}" border="0" style="cursor:pointer;{% if loop.index0 == 0 %}display:none;{% endif %}" />
											</td>
											{% if loop.index0 == 0 %}
												<td class="FreeShippingColumn">
													<label><input id="domesticYesFreeFlatShipping" name="domesticYesFreeFlatShipping" type="checkbox" {% if domesticFreeShipping %}checked="checked"{% endif %} />{{ lang.YesFreeShipping }}</label>
												</td>
											{% endif %}
										</tr>
									{% endfor %}
								{% else %}
									<tr>
										<td>
											<select name="domesticShippingServFlat[0][Type]" class="domesticShippingServFlatType Field300">
												<option value="">{{ lang.OptionSeparator }}</option>
												{% for carrier, services in DomesticShippingServFlat %}
													<optgroup label="{{ carrier }}">
														{% for serviceCode, service in services %}
															<option value="{{ serviceCode }}" {% if service.class %}class="{{ service.class }}"{% endif %}>{{ service.name }}</option>
														{% endfor %}
													</optgroup>
												{% endfor %}
											</select>
										</td>
										<td>
											{{ currencyToken }} <input type="text" name="domesticShippingServFlat[0][Cost]" class="Field50 domesticShippingServFlatCost" value="" />
										</td>
										<td>
											{{ currencyToken }} <input type="text" name="domesticShippingServFlat[0][MoreCost]" class="Field50 domesticShippingServFlatMoreCost" value="" />
										</td>
										<td>
											<img class="ShippingServiceAdd" src="images/addicon.gif" alt="{% jslang 'Add' %}" border="0" style="cursor:pointer;" />
											<img class="ShippingServiceRemove" src="images/delicon.gif" alt="{% jslang 'Remove' %}" border="0" style="cursor:pointer;display:none;" />
										</td>
										<td class="FreeShippingColumn">
											<label><input id="domesticYesFreeFlatShipping" name="domesticYesFreeFlatShipping" type="checkbox" {% if domesticFreeShipping %}checked="checked"{% endif %} />{{ lang.YesFreeShipping }}</label>
										</td>
									</tr>
								{% endif %}
							</table>
							{% if domesticPickupAllowed %}
								<div>
									<img src="images/nodejoin.gif" alt="" align="top" />
									<div style="display: inline-block;">
										<label><input id="domesticLocalPickup" name="domesticLocalPickup" type="checkbox" {% if domesticFlatCount == 3 %}disabled="disabled"{% elseif domesticLocalPickup %}checked="checked"{% endif %}/>{{ lang.YesLocalPickup }}</label>
									</div>
								</div>
							{% endif %}
						</div>

						<!-- Calculated options -->
						<div id="domesticServiceTypeCalculated" style="margin-left: 20px;">
							<div>
								<img src="images/nodejoin.gif" alt="" align="top"/>
								<div style="display: inline-block;">
									<label>{{ lang.PackageType }}</label>
									{{ form.select('domesticShippingPackage', DomesticShippingPackage, domesticPackageType) }}
								</div>
							</div>
							<div>
								<img src="images/nodejoin.gif" alt="" align="top"/>
								<table cellspacing="0" cellpadding="2" style="display: inline-table;" class="domestic Calculated">
									<tr>
										<td style="width:307px;">
											{{ lang.ShippingServices }}
										</td>
									</tr>
									{% if domesticShippingCostType == 'Calculated' %}
										{% for currentServiceId, serviceOptions in domesticCalculatedShippingServices %}
											<tr>
												<td>
													<select name="domesticShippingServCalculated[{{ loop.index0 }}][Type]" class="domesticShippingServCalculatedType Field300">
														<option value="">{{ lang.OptionSeparator }}</option>
														{% for carrier, services in DomesticShippingServCalculated %}
															<optgroup label="{{ carrier }}">
																{% for serviceCode, service in services %}
																	<option value="{{ serviceCode }}" {% if service.class %}class="{{ service.class }}"{% endif %} {% if serviceCode == currentServiceId %}selected="selected"{% endif %}>{{ service.name }}</option>
																{% endfor %}
															</optgroup>
														{% endfor %}
													</select>
												</td>
												<td>
													<img class="ShippingServiceAdd" src="images/addicon.gif" alt="{% jslang 'Add' %}" border="0" style="cursor:pointer;" />
													<img class="ShippingServiceRemove" src="images/delicon.gif" alt="{% jslang 'Remove' %}" border="0" style="cursor:pointer;{% if loop.index0 == 0 %}display:none;{% endif %}" />
												</td>
												{% if loop.index0 == 0 %}
													<td class="FreeShippingColumn">
														<label><input id="domesticYesFreeCalculatedShipping" name="domesticYesFreeCalculatedShipping" type="checkbox" {% if domesticFreeShipping %}checked="checked"{% endif %} />{{ lang.YesFreeShipping }}</label>
													</td>
												{% endif %}
											</tr>
										{% endfor %}
									{% else %}
										<tr>
											<td>
												<select name="domesticShippingServCalculated[0][Type]" class="domesticShippingServCalculatedType Field300">
													<option value="">{{ lang.OptionSeparator }}</option>
													{% for carrier, services in DomesticShippingServCalculated %}
														<optgroup label="{{ carrier }}">
															{% for serviceCode, service in services %}
																<option value="{{ serviceCode }}" {% if service.class %}class="{{ service.class }}"{% endif %}>{{ service.name }}</option>
															{% endfor %}
														</optgroup>
													{% endfor %}
												</select>
											</td>
											<td>
												<img class="ShippingServiceAdd" src="images/addicon.gif" alt="{% jslang 'Add' %}" border="0" style="cursor:pointer;" />
												<img class="ShippingServiceRemove" src="images/delicon.gif" alt="{% jslang 'Remove' %}" border="0" style="cursor:pointer;display:none;" />
											</td>
											<td class="FreeShippingColumn">
												<label><input id="domesticYesFreeCalculatedShipping" name="domesticYesFreeCalculatedShipping" type="checkbox" {% if domesticFreeShipping %}checked="checked"{% endif %} />{{ lang.YesFreeShipping }}</label>
											</td>
										</tr>
									{% endif %}
								</table>
							</div>
						</div>

						<div id="domesticGetItFastRow" style="margin-left: 20px;">
							<img src="images/nodejoin.gif" alt="" align="top" />
							<div style="display: inline-block;">
								<label><input id="domesticYesGetItFast" name="domesticYesGetItFast" type="checkbox" {% if domesticGetItFast %}checked="checked"{% endif %} />{{ lang.YesGetItFast }}</label>
								{{ util.tooltip('GetItFastHelpTitle', 'GetItFastHelp') }}
							</div>
						</div>
					</div>
				{{ form.endRow }}

				{{ form.startRowGroup([ 'id': 'domesticHandlingOptions' ]) }}

				{{ form.startRow([ 'label': lang.HandlingCost, 'id': 'domesticHandlingCostRow', 'hidden': true ]) }}
					{{ currencyToken }} <input type="text" name="domesticHandlingCost" id="domesticHandlingCost" class="Field50" value="{{ domesticHandlingCost|formatPrice(false, false, false, currency) }}" />
				{{ form.endRow }}

				{{ form.endRowGroup }}

			{{ form.endForm }}
		</div>

		<!-- International shipping -->
		<div class="shippingContent" id="internationalTabContent" style="display: none;">
			{{ form.startForm }}
				{{ form.startRow([ 'label': lang.InternationalShipping ~ ':' ]) }}
					<label><input id="yesInternationalShipping" name="yesInternationalShipping" type="checkbox" {% if useInternationalShipping %}checked="checked"{% endif %} />{{ lang.YesInternationalShipping }}</label>
				{{ form.endRow }}

				{{ form.startRowGroup([ 'id': 'internationalShippingContent', 'hidden': not useInternationalShipping ]) }}
					{{ form.startRow([ 'label': lang.ShippingMethods ]) }}
						<div>
							<img src="images/nodejoin.gif" alt="" />
							{{ lang.ShippingCostType }}
							{{ form.select('internationalShippingType', internationalShippingCostTypes, internationalShippingCostType) }}
						</div>

						<!-- Flat rate options -->
						<div id="internationalServiceTypeFlat" style="margin-left: 20px;">
							<img src="images/nodejoin.gif" alt="" align="top"/>
							<table cellspacing="0" cellpadding="2" style="display: inline-table;" class="international Flat">
								<tr>
									<td>
										{{ lang.ShipTo }}
									</td>
									<td style="width:307px;">
										{{ lang.ShippingServices }}
									</td>
									<td style="width:71px;">
										{{ lang.FirstItemCost }}
									</td>
									<td class="Field80">
										{{ lang.EachAdditional }}
									</td>
								</tr>
								{% if internationalShippingCostType == 'Flat' %}
									{% for currentServiceId, serviceOptions in internationalFlatShippingServices %}
										<tr>
											<td>
												<select name="internationalShippingServFlat[{{ loop.index0 }}][ShipTo]" class="internationalShippingServFlatShipTo Field150">
													<option value="">{{ lang.PleaseSelectLocation }}</option>
													{% for locationCode, description in ShipToLocations %}
														<option value="{{ locationCode }}" {% if locationCode == serviceOptions.ship_to_location %}selected="selected"{% endif %}>{{ description }}</option>
													{% endfor %}
												</select>
											</td>
											<td>
												<select name="internationalShippingServFlat[{{ loop.index0 }}][Type]" class="internationalShippingServFlatType Field300">
													<option value="">{{ lang.OptionSeparator }}</option>
													{% for carrier, services in InternationalShippingServFlat %}
														<optgroup label="{{ carrier }}">
															{% for serviceCode, service in services %}
																<option value="{{ serviceCode }}" {% if service.class %}class="{{ service.class }}"{% endif %} {% if serviceCode == currentServiceId %}selected="selected"{% endif %}>{{ service.name }}</option>
															{% endfor %}
														</optgroup>
													{% endfor %}
												</select>
											</td>
											<td>
												{{ currencyToken }} <input type="text" name="internationalShippingServFlat[{{ loop.index0 }}][Cost]" class="Field50 internationalShippingServFlatCost" value="{{ serviceOptions.cost|formatPrice(false, false, false, currency) }}" />
											</td>
											<td>
												{{ currencyToken }} <input type="text" name="internationalShippingServFlat[{{ loop.index0 }}][MoreCost]" class="Field50 internationalShippingServFlatMoreCost" value="{{ serviceOptions.additional_cost|formatPrice(false, false, false, currency) }}" />
											</td>
											<td>
												<img class="ShippingServiceAdd" src="images/addicon.gif" alt="{% jslang 'Add' %}" border="0" style="cursor:pointer;" />
												<img class="ShippingServiceRemove" src="images/delicon.gif" alt="{% jslang 'Remove' %}" border="0" style="cursor:pointer;{% if loop.index0 == 0 %}display:none;{% endif %}" />
											</td>
										</tr>
									{% endfor %}
								{% else %}
									<tr>
										<td>
											<select name="internationalShippingServFlat[0][ShipTo]" class="internationalShippingServFlatShipTo Field150">
												<option value="">{{ lang.PleaseSelectLocation }}</option>
												{% for locationCode, description in ShipToLocations %}
													<option value="{{ locationCode }}">{{ description }}</option>
												{% endfor %}
											</select>
										</td>
										<td>
											<select name="internationalShippingServFlat[0][Type]" class="internationalShippingServFlatType Field300">
												<option value="">{{ lang.OptionSeparator }}</option>
												{% for carrier, services in InternationalShippingServFlat %}
													<optgroup label="{{ carrier }}">
														{% for serviceCode, service in services %}
															<option value="{{ serviceCode }}" {% if service.class %}class="{{ service.class }}"{% endif %} {% if serviceCode == currentServiceId %}selected="selected"{% endif %}>{{ service.name }}</option>
														{% endfor %}
													</optgroup>
												{% endfor %}
											</select>
										</td>
										<td>
											{{ currencyToken }} <input type="text" name="internationalShippingServFlat[0][Cost]" class="Field50 internationalShippingServFlatCost" value="" />
										</td>
										<td>
											{{ currencyToken }} <input type="text" name="internationalShippingServFlat[0][MoreCost]" class="Field50 internationalShippingServFlatMoreCost" value="" />
										</td>
										<td>
											<img class="ShippingServiceAdd" src="images/addicon.gif" alt="{% jslang 'Add' %}" border="0" style="cursor:pointer;" />
											<img class="ShippingServiceRemove" src="images/delicon.gif" alt="{% jslang 'Remove' %}" border="0" style="cursor:pointer;display:none;" />
										</td>
									</tr>
								{% endif %}
							</table>
						</div>

						<!-- Calculated options -->
						<div id="internationalServiceTypeCalculated" style="margin-left: 20px;">
							<div>
								<img src="images/nodejoin.gif" alt="" align="top"/>
								<div style="display: inline-block;">
									<label>{{ lang.PackageType }}</label>
									{{ form.select('internationalShippingPackage', InternationalShippingPackage, internationalPackageType) }}
								</div>
							</div>
							<div>
								<img src="images/nodejoin.gif" alt="" align="top"/>
								<table cellspacing="0" cellpadding="2" style="display: inline-table;" class="international Calc">
									<tr>
										<td>
											{{ lang.ShipTo }}
										</td>
										<td style="width:307px;">
											{{ lang.ShippingServices }}
										</td>
									</tr>
									{% if internationalShippingCostType == 'Calculated' %}
										{% for currentServiceId, serviceOptions in internationalCalculatedShippingServices %}
											<tr>
												<td>
													<select name="internationalShippingServCalculated[{{ loop.index0 }}][ShipTo]" class="internationalShippingServCalculatedShipTo Field150">
														<option value="">{{ lang.PleaseSelectLocation }}</option>
														{% for locationCode, description in ShipToLocations %}
															<option value="{{ locationCode }}" {% if locationCode == serviceOptions.ship_to_location %}selected="selected"{% endif %}>{{ description }}</option>
														{% endfor %}
													</select>
												</td>
												<td>
													<select name="internationalShippingServCalculated[{{ loop.index0 }}][Type]" class="internationalShippingServCalculatedType Field300">
														<option value="">{{ lang.OptionSeparator }}</option>
														{% for carrier, services in InternationalShippingServCalculated %}
															<optgroup label="{{ carrier }}">
																{% for serviceCode, service in services %}
																	<option value="{{ serviceCode }}" {% if service.class %}class="{{ service.class }}"{% endif %} {% if serviceCode == currentServiceId %}selected="selected"{% endif %}>{{ service.name }}</option>
																{% endfor %}
															</optgroup>
														{% endfor %}
													</select>
												</td>
												<td>
													<img class="ShippingServiceAdd" src="images/addicon.gif" alt="{% jslang 'Add' %}" border="0" style="cursor:pointer;" />
													<img class="ShippingServiceRemove" src="images/delicon.gif" alt="{% jslang 'Remove' %}" border="0" style="cursor:pointer;{% if loop.index0 == 0 %}display:none;{% endif %}" />
												</td>
											</tr>
										{% endfor %}
									{% else %}
										<tr>
											<td>
												<select name="internationalShippingServCalculated[0][ShipTo]" class="internationalShippingServCalculatedShipTo Field150">
													<option value="">{{ lang.PleaseSelectLocation }}</option>
													{% for locationCode, description in ShipToLocations %}
														<option value="{{ locationCode }}">{{ description }}</option>
													{% endfor %}
												</select>
											</td>
											<td>
												<select name="internationalShippingServCalculated[0][Type]" class="internationalShippingServCalculatedType Field300">
													<option value="">{{ lang.OptionSeparator }}</option>
													{% for carrier, services in InternationalShippingServCalculated %}
														<optgroup label="{{ carrier }}">
															{% for serviceCode, service in services %}
																<option value="{{ serviceCode }}" {% if service.class %}class="{{ service.class }}"{% endif %} {% if serviceCode == currentServiceId %}selected="selected"{% endif %}>{{ service.name }}</option>
															{% endfor %}
														</optgroup>
													{% endfor %}
												</select>
											</td>
											<td>
												<img class="ShippingServiceAdd" src="images/addicon.gif" alt="{% jslang 'Add' %}" border="0" style="cursor:pointer;" />
												<img class="ShippingServiceRemove" src="images/delicon.gif" alt="{% jslang 'Remove' %}" border="0" style="cursor:pointer;display:none;" />
											</td>
										</tr>
									{% endif %}
								</table>
							</div>
						</div>
					{{ form.endRow }}

					{{ form.startRow([ 'label': lang.HandlingCost, 'id': 'internationalHandlingCostRow', 'hidden': true ]) }}
						{{ currencyToken }} <input type="text" name="internationalHandlingCost" id="internationalHandlingCost" class="Field50" value="{{ internationalHandlingCost|formatPrice(false, false, false, currency) }}" />
					{{ form.endRow }}

				{{ form.endRowGroup }}

			{{ form.endForm }}
		</div>

	{{ form.endForm }}

	<!-- Additional Shipping Details -->
	{{ form.startForm }}
		{{ form.heading(lang.AdditionalShippingDetails) }}

		{{ form.startRow([ 'label': lang.HandlingTime, 'required': true ]) }}
			{{ form.select('handlingTime', handlingTimes, handlingTime) }}
			{{ util.tooltip('HandlingTimeHelpTitle', 'HandlingTimeHelp') }}
		{{ form.endRow }}

		{% if hasSalesTaxStates %}
			{{ form.startRow([ 'label': lang.SalesTax, 'required': true ]) }}
				<label><input type="radio" name="salesTax" id="salesTax_None" value="0" {% if useSalesTax == false %}checked="checked"{% endif %} />{{ lang.NoSalesTax }}</label>
				<br />
				<label><input type="radio" name="salesTax" id="salesTax_State" value="1" {% if useSalesTax %}checked="checked"{% endif %} />{{ lang.StateSalesTax }}</label>
				<div class="NodeJoin" {% if useSalesTax == false %}style="display: none;"{% endif %}>
					<div>
						<img src="images/nodejoin.gif" alt="" />
						{{ form.select('salesTaxState', salesTaxStates, salesTaxState) }}
						<input type="text" name="salesTaxPercentage" id="salesTaxPercentage" value="{{ salesTaxPercent }}" class="Field40" />%
					</div>
					<div>
						<img src="images/nodejoin.gif" alt="" />
						<label><input id="salesTaxIncludeShippingCost" name="salesTaxIncludeShippingCost" type="checkbox" value="1" {% if salesTaxIncludesShipping %}checked="checked"{% endif %} />{{ lang.ShippingInSalesTax }}</label>
					</div>
				</div>
			{{ form.endRow }}
		{% endif %}
	{{ form.endForm }}
</div>

<!-- Other Tab -->
<div class="mainContent" id="otherTabContent" style="display: none;">
	<!-- Checkout Instructions and Return Policy -->
	{{ form.startForm }}
		{{ form.heading(lang.CheckoutReturnPolicyDetails) }}

		{{ form.startRow([ 'label': lang.CheckoutInstructions, 'required': false ]) }}
			<textarea name="checkoutInstructions" id="checkoutInstructions" class="Field250" rows="6">{{ checkoutInstructions }}</textarea>
		{{ form.endRow }}

		{{ form.startRow([ 'label': lang.DoYouAcceptReturns ]) }}
			<label><input id="acceptReturns" name="acceptReturns" type="checkbox"  {% if acceptReturns %}checked="checked"{% endif %}/>{% lang 'YesAcceptReturn' %}</label>
		{{ form.endRow }}

		{{ form.startRowGroup([ 'id': 'returnOptions', 'hidden': not acceptReturns ]) }}

			{% if refundSupported %}
				{{ form.startRow([ 'label': lang.ReturnOfferedAs, 'required': true ]) }}
					{{ form.select('refundOption', refundOptions, returnOfferedAs) }}
				{{ form.endRow }}
			{% endif %}

			{% if returnsWithinSupported %}
				{{ form.startRow([ 'label': lang.ReturnsPeriod, 'required': true ]) }}
					{{ form.select('returnsWithin', returnsWithinOptions, returnsPeriod) }}
				{{ form.endRow }}
			{% endif %}

			{% if returnCostPaidBySupported %}
				{{ form.startRow([ 'label': lang.ReturnShippingCostBy, 'required': true ]) }}
					{{ form.select('returnCostPaidBy', returnCostPaidByOptions, returnCostPaidBy) }}
				{{ form.endRow }}
			{% endif %}

			{% if returnDescriptionSupported %}
				{{ form.startRow([ 'label': lang.AdditionalPolicyInfo ]) }}
					<textarea name="additionalPolicyInfo" id="additionalPolicyInfo" class="Field250" rows="6">{{ additionalPolicyInfo }}</textarea>
				{{ form.endRow }}
			{% endif %}

		{{ form.endRowGroup }}
	{{ form.endForm }}

	<!-- Hit Counter -->
	{{ form.startForm }}
		{{ form.heading(lang.FreeVisitorCounter) }}

		{{ form.startRow([ 'label': lang.CounterStyle ]) }}
			{{ form.radioList('hitCounter', hitCounters, hitCounter) }}
		{{ form.endRow }}
	{{ form.endForm }}

	<!-- Paid Upgrade Options-->
	{{ form.startForm }}
		{{ form.heading(lang.PaidListingUpgrade) }}

		{{ form.startRow([ 'label': lang.GalleryOptions ]) }}
			{{ form.radioList('galleryOption', galleryOptions, galleryOption) }}
			<div class="NodeJoin" style="display: none;">
				<img src="images/nodejoin.gif" alt="" />
				<label>
					{{ lang.GalleryDuration }}
					<select id="galleryDuration" name="galleryDuration">
						<option value="Days_7">{{ lang.EbayDurationDays_7 }}</option>
						<option value="LifeTime">{{ lang.EbayDurationLifeTime }}</option>
					</select>
				</label>
			</div>
		{{ form.endRow }}

		{% if listingFeatures %}
			{{ form.startRow([ 'label': lang.ListingFeatures ]) }}
				{% for featureCode, featureName in listingFeatures %}
					<label><input type="checkbox" name="listingFeature[{{ loop.index0 }}]" id="listingFeature_{{ featureCode }}" value="{{ featureCode }}" {%if featureCode in selectedListingFeatures %}checked="checked"{% endif %} />{{ featureName }}</label>
					<br />
				{% endfor %}
			{{ form.endRow }}
		{% endif %}
	{{ form.endForm }}

	{{ form.startForm }}
		{{ form.heading(lang.MiscellaneousSettings) }}

		{% if options.lot_size_enabled %}
			{{ form.startRow([ 'label': lang.LotSize ]) }}
				<input type="text" id="lotSize" name="lotSize" class="Field20" value="{{ lotSize }}" />
				{{ util.tooltip('LotSizeHelpTitle', 'LotSizeHelp') }}
			{{ form.endRow }}
		{% endif %}
	{{ form.endForm }}
</div>


<script type="text/javascript">//<![CDATA[
	var servicesCount = {
		domestic: {
			Flat: {{ domesticFlatCount }},
			Calculated: {{ domesticCalcCount }}
		},
		international: {
			Flat: {{ internationalFlatCount }},
			Calculated: {{ internationalCalcCount }}
		}
	};

	$(document).ready(function() {
		// main tab switching
		$("#mainTabs a").click(function() {
			if ($(this).hasClass('active')) {
				return;
			}

			$(this).parents('ul').find('a').removeClass('active');
			$(this).addClass('active');

			var id = $(this).attr('id');

			$(".mainContent").hide();
			$("#" + id + "Content").show();

			return false;
		});

		$("#generalTab").click();

		// display quantity options
		$("input[name='quantityType']").click(function() {
			if (!$(this).attr('checked')) {
				return;
			}

			if ($(this).val() == 'one') {
				$(this).parent('label').nextAll('.NodeJoin:first').hide();
			}
			else {
				$(this).parent('label').nextAll('.NodeJoin:first').show();
			}
		});

		// selling method switching
		$("input[name='sellingMethod']").click(function() {
			if (!$(this).attr('checked')) {
				return;
			}

			var sellingMethod = $(this).val();

			if (sellingMethod == 'Chinese') {
				$("#auctionTabContent").show();
				$("#fixedPriceTabContent").hide();
			}
			else {
				$("#auctionTabContent").hide();
				$("#fixedPriceTabContent").show();
			}

			EbayTemplate.sellingMethod = sellingMethod;
			EbayTemplate.CheckGetItFastAvailable();
		});

		{% if sellingMethod == 'Chinese' %}
			$("#auctionTabContent").show();
		{% elseif sellingMethod == 'FixedPriceItem' %}
			$("#fixedPriceTabContent").show();
		{% endif %}
		EbayTemplate.sellingMethod = '{{ sellingMethod }}';


		// shipping tab switching
		$("#shippingTabs a").click(function() {
			if ($(this).hasClass('active')) {
				return;
			}

			$(this).parents('ul').find('a').removeClass('active');
			$(this).addClass('active');

			var id = $(this).attr('id');

			$(".shippingContent").hide();
			$("#" + id + "Content").show();

			return false;
		});

		$("#domesticTab").click();

		// reserve price settings
		$("#useReservePrice").change(function() {
			if($(this).is(':checked')) {
				$(this).parent('label').nextAll('.NodeJoin:first').show();
			}
			else {
				$(this).parent('label').nextAll('.NodeJoin:first').hide();
			}
		});

		// buy it now settings
		$("#useBuyItNowPrice").change(function() {
			if($(this).is(':checked')) {
				$(this).parent('label').nextAll('.NodeJoin:first').show();
			}
			else {
				$(this).parent('label').nextAll('.NodeJoin:first').hide();
			}

			EbayTemplate.CheckGetItFastAvailable();
		});

		$("#acceptReturns").click(function() {
			if ($(this).attr('checked')) {
				$("#returnOptions").show();
			}
			else {
				$("#returnOptions").hide();
			}
		});

		$("input[name='galleryOption']").click(function() {
			if (!$(this).attr('checked')) {
				return;
			}

			if ($(this).val() == 'Featured') {
				$(this).parent('label').nextAll('.NodeJoin:first').show();
			}
			else {
				$(this).parent('label').nextAll('.NodeJoin:first').hide();
			}
		});

		$("input[name='salesTax']").click(function() {
			if (!$(this).attr('checked')) {
				return;
			}

			if ($(this).val() == '0') {
				$(this).parent('label').nextAll('.NodeJoin:first').hide();
			}
			else {
				$(this).parent('label').nextAll('.NodeJoin:first').show();
			}
		});

		// initialises common options for domestic and international
		function initShippingDetails(shippingArea) {
			var shippingAreaLower = shippingArea.toLowerCase();

			// changing the shipping type between flat or calculated
			$("#" + shippingAreaLower + "ShippingType").change(function() {
				var shippingType = $(this).val();

				if (shippingType == 'Flat') {
					$("#" + shippingAreaLower + "ServiceTypeFlat").show();
					$("#" + shippingAreaLower + "ServiceTypeCalculated").hide();
					$("#" + shippingAreaLower + "HandlingCostRow").hide();
				}
				else {
					$("#" + shippingAreaLower + "ServiceTypeFlat").hide();
					$("#" + shippingAreaLower + "ServiceTypeCalculated").show();
					$("#" + shippingAreaLower + "HandlingCostRow").show();
				}
			});
			$("#" + shippingAreaLower + "ShippingType").change();

			// changing the calculated shipping package type
			$('#' + shippingAreaLower + 'ShippingPackage').change(function () {
				var packageType = $(this).val();
				var objectName = "." + shippingAreaLower + 'ShippingServCalculatedType';

				// hiding select options is not cross browser compatible, so disable instead
				$(objectName + " option").removeAttr('disabled');
				$(objectName + " option").not('.' + packageType).attr('disabled', 'disabled');
				$(objectName).find('option:first').removeAttr('disabled');
				$(objectName).find("option:selected:disabled").parent().find('option:first').attr('selected', 'selected');

				$(objectName).change();
			});
			$('#' + shippingAreaLower + 'ShippingPackage').change();
		}

		initShippingDetails('Domestic');
		initShippingDetails('International');

		$("input[name='domesticShipping']").click(function() {
			if (!$(this).attr('checked')) {
				return;
			}

			if ($(this).val() == 'pickup') {
				$(this).parent('label').nextAll('.NodeJoin:first').hide();
				$("#domesticHandlingOptions").hide();
			}
			else {
				$(this).parent('label').nextAll('.NodeJoin:first').show();
				$("#domesticHandlingOptions").show();
			}
		});

		// clones a shipping service row
		$(".ShippingServiceAdd").click(function() {
			var row = $(this).closest('tr');
			var table = $(this).closest('table');

			if (table.hasClass('domestic')) {
				var shippingArea = 'domestic';
			}
			else {
				var shippingArea = 'international'
			}

			if (table.hasClass('Flat')) {
				var shippingType = 'Flat';
			}
			else {
				var shippingType = 'Calculated';
			}

			// amount of existing services
			var totalServices = row.siblings().length;

			// allow a max of 2 domestic flat services if local pickup is ticked
			if (shippingArea == 'domestic' &&
				shippingType == 'Flat' &&
				totalServices == 2 &&
				$("#domesticLocalPickup").attr('checked')) {

				alert(lang.MaxServiceAllowedWithPickup);
				return;
			}
			// allow a max of 3 services for other conditions
			else if (totalServices == 3) {
				alert(lang.MaxServiceAllowed);
				return;
			}

			var newRow = row.clone(true);
			var newRowIndex = ++servicesCount[shippingArea][shippingType];

			var className = '.' + shippingArea + 'ShippingServ' + shippingType;
			var fieldName = shippingArea + 'ShippingServ' + shippingType + '[' + newRowIndex + ']';

			newRow.find(className + 'ShipTo').attr('name', fieldName + '[ShipTo]');
			newRow.find(className + 'Type').attr('name', fieldName + '[Type]');
			newRow.find(className + 'Cost').attr('name', fieldName + '[Cost]').removeAttr('disabled');
			newRow.find(className + 'MoreCost').attr('name', fieldName + '[MoreCost]').removeAttr('disabled');
			newRow.find('.FreeShippingColumn').remove();
			newRow.find(':input').val('');
			newRow.find('.ShippingServiceRemove').show();

			newRow.insertAfter(row);

			if (shippingArea == 'domestic' && shippingType == 'Flat') {
				checkDomesticFlatPickupEnabled(table);
			}
		});

		// removes a shipping service row
		$(".ShippingServiceRemove").click(function() {
			var table = $(this).closest('table');
			$(this).closest('tr').remove();

			if (table.hasClass('domestic') && table.hasClass('Flat')) {
				checkDomesticFlatPickupEnabled(table);
			}
		});

		// display international shipping options
		$("#yesInternationalShipping").click(function() {
			if ($(this).attr('checked')) {
				$("#internationalShippingContent").show();
			}
			else {
				$("#internationalShippingContent").hide();
			}
		});

		$("#domesticYesGetItFast").click(function() {
			if ($(this).attr('checked')) {
				$("#handlingTime option:first").attr('selected', 'selected');
				$("#handlingTime").attr('disabled', 'disabled');
			}
			else {
				$("#handlingTime").removeAttr('disabled');
			}
		});

		$('.domesticShippingServCalculatedType, .domesticShippingServFlatType, #domesticShippingType').live('change', function () {
			EbayTemplate.CheckGetItFastAvailable();
		});

		function checkDomesticFlatPickupEnabled(table)
		{
			// amount of existing services
			var totalServices = table.find('tr').length - 1;

			if (totalServices == 3) {
				$("#domesticLocalPickup").attr('disabled', 'disabled');
				$("#domesticLocalPickup").removeAttr('checked');
			}
			else {
				$("#domesticLocalPickup").removeAttr('disabled');
			}
		}

		// disable the cost/additional cost inputs if ticking the free shipping option
		$("#domesticYesFreeFlatShipping").click(function() {
			if ($(this).attr('checked')) {
				$('.domesticShippingServFlatCost:first').attr('disabled', 'disabled');
				$('.domesticShippingServFlatMoreCost:first').attr('disabled', 'disabled');
			}
			else {
				$('.domesticShippingServFlatCost:first').removeAttr('disabled');
				$('.domesticShippingServFlatMoreCost:first').removeAttr('disabled');
			}
		});

		// notify user if trying to click the local pickup option
		$("#domesticLocalPickup").parent('label').click(function() {
			if ($("#domesticLocalPickup").attr('disabled')) {
				alert(lang.MaxServiceAllowedWithPickup);
			}
		});

		// notify user if trying to click the get it fast option
		$("#domesticYesGetItFast").parent('label').click(function() {
			if ($("#domesticYesGetItFast").attr('disabled')) {
				alert(lang.GetItFastDisabled);
			}
		});

		EbayTemplate.CheckGetItFastAvailable();
	});

	lang.EnterQuantity = '{% jslang 'EnterQuantity' %}';
	lang.EnterLotSize = '{% jslang 'EnterLotSize' %}';
	lang.EnterReservePrice = '{% jslang 'EnterReservePrice' %}';
	lang.MinReserveCustomPrice = '{% jslang 'MinReserveCustomPrice' %}';
	lang.EnterStartPrice = '{% jslang 'EnterStartPrice' %}';
	lang.EnterBuyItNowPrice = '{% jslang 'EnterBuyItNowPrice' %}';
	lang.MinimumReserveNotMet = '{% jslang 'MinimumReserveNotMet' %}';
	lang.ReservePriceNotAllowed = '{% jslang 'ReservePriceNotAllowed' %}';
	lang.BuyItNowPriceTooLow = '{% jslang 'BuyItNowPriceTooLow' %}';

	lang.ChooseSellingMethod = '{% jslang 'ChooseSellingMethod' %}';

	lang.SelectPaymentMethod = '{% jslang 'SelectPaymentMethod' %}';
	lang.EnterPayPalEmail = '{% jslang 'EnterPayPalEmail' %}';

	lang.EnterShippingService = '{% jslang 'EnterShippingService' %}';
	lang.EnterShippingServiceCost = '{% jslang 'EnterShippingServiceCost' %}';
	lang.EnterShipToLocation = '{% jslang 'EnterShipToLocation' %}';
	lang.MaxServiceAllowed = '{% jslang 'MaxServiceAllowed' %}';
	lang.MaxServiceAllowedWithPickup ='{% jslang 'MaxServiceAllowedWithPickup' %}';
	lang.GetItFastDisabled ='{% jslang 'GetItFastDisabled' %}';

	lang.EnterCityStateDetails = '{% jslang 'EnterCityStateDetails' %}';
	lang.EnterZipPostcodeDetails = '{% jslang 'EnterZipPostcodeDetails' %}';
	lang.EnterItemTitle = '{% jslang 'EnterItemTitle' %}';
	lang.EnterItemSku = '{% jslang 'EnterItemSku' %}';
	lang.EnterItemDescription = '{% jslang 'EnterItemDescription' %}';
	lang.EnterInternetPhoto = '{% jslang 'EnterInternetPhoto' %}';
	lang.EnterSalesTaxPercentage = '{% jslang 'EnterSalesTaxPercentage' %}';
	lang.EnterListingCategory1 = '{% jslang 'EnterListingCategory1' %}';
	lang.EnterHandlingCost = '{% jslang 'EnterHandlingCost' %}';
//]]></script>
