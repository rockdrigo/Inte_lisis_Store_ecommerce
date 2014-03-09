{% import 'macros/util.tpl' as util %}

	<form action="index.php?ToDo=saveUpdatedCheckoutSettings" name="frmCheckoutSettings" id="frmCheckoutSettings" method="post" onsubmit="return ValidateForm(CheckCheckoutSettingsForm)">
		<div class="BodyContainer">
		<table cellSpacing="0" cellPadding="0" width="100%" style="margin-left: 4px; margin-top: 8px;">
		<tr>
			<td class="Heading1">{% lang 'CheckoutSettings' %}</td>
		</tr>
		<tr>
			<td class="Intro">
				<p>{% lang 'CheckoutSettingsIntro' %}</p>
				<div id="CheckoutStatus">
					{{ Message|safe }}
				</div>
				<p class="TopButtons">
					<input type="submit" value="{% lang 'Save' %}" class="FormButton SaveButton" />
					<input type="reset" value="{% lang 'Cancel' %}" class="FormButton CancelButton" onclick="ConfirmCancel()" />
				</p>
			</td>
		</tr>
		<tr>
			<td>
				<ul id="tabnav">
					<li><a href="#" class="active" id="tab0" onclick="ShowTab(0)">{% lang 'GeneralSettings' %}</a></li>
					{{ CheckoutTabs|safe }}
				</ul>
			</td>
		</tr>
		<tr>
			<td>
				<input id="currentTab" name="currentTab" value="0" type="hidden">
				<div id="div0" style="padding-top: 10px;">
					<table width="100%" class="Panel">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'CheckoutSettings' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<label for="storename">{% lang 'CheckoutMethods' %}:</label>
							</td>
							<td>
								<div id="BuiltInGatewayOption" style="{{ HideBuiltInGateway|safe }}">
									<div>
										<label><input type="radio" id="CheckoutMethodBuiltIn" name="builtInGateway" value="1" {{ UseBuiltInGatewayChecked|safe }} /> {{ UseBuiltInGateway|safe }}</label>
									</div>
									<div class="builtInGateway builtInGateway_1">
										<img src="images/nodejoin.gif" alt="" class="FloatLeft" />
										<div class="BuiltInGatewayForm">
											{{ BuiltInGatewayErrors|safe }}
											<div class="BuiltInCheckoutIntro">
												{{ BuiltInGatewayIntro|safe }}
											</div>
											{{ BuiltInGatewayProperties|safe }}
										</div>
									</div>

									<div>
										<label><input type="radio" id="CheckoutMethodCustom" name="builtInGateway" value="0" {{ UseCustomGatewayChecked|safe }} /> {% lang 'UseCustomGateway' %}</label>
									</div>
									<img src="images/nodejoin.gif" alt="" class="FloatLeft builtInGateway builtInGateway_0" />
								</div>
								<div class="builtInGateway builtInGateway_0">
									<select size="20" multiple="multiple" name="checkoutproviders[]" id="checkoutproviders" class="Field300 ISSelectReplacement {{ CheckoutProviderClass|safe }}">
										{{ CheckoutProviders|safe }}
									</select>
									<img onmouseout="HideHelp('d1');" onmouseover="ShowHelp('d1', '{% lang 'CheckoutMethods' %}', '{% lang 'CheckoutMethodsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
									<div style="display:none" id="d1"></div>
								</div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">{% lang 'CheckoutType' %}:</td>
							<td>
								<select name="CheckoutType" class="Field300">
									<option value="single" {{ CheckoutTypeSingleSelected|safe }}>{% lang 'CheckoutTypeSingle' %}</option>
									<option value="multipage" {{ CheckoutTypeMultiSelected|safe }}>{% lang 'CheckoutTypeMulti' %}</option>
								</select>
								<img onmouseout="HideHelp('chktype');" onmouseover="ShowHelp('chktype', '{% lang 'CheckoutType' %}', '{% lang 'CheckoutTypeHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="chktype"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">{% lang 'EnableGuestCheckout' %}?</td>
							<td>
								<label><input type="checkbox" name="GuestCheckoutEnabled" value="1" onclick="$('.GuestCheckoutEnabledToggle').toggle();" {{ GuestCheckoutChecked|safe }} /> {% lang 'YesEnableGuestCheckout' %}</label>
								<img onmouseout="HideHelp('guestcheckout');" onmouseover="ShowHelp('guestcheckout', '{% lang 'EnableGuestCheckout' %}?', '{% lang 'EnableGuestCheckoutHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="guestcheckout"></div>
								<div style="{{ HideGuestCheckoutCreateAccounts|safe }}" class="GuestCheckoutEnabledToggle">
									<img src="images/nodejoin.gif" alt="" />
									<label><input type="checkbox" name="GuestCheckoutCreateAccounts" value="1" {{ GuestCheckoutCreateAccountsCheck|safe }} /> {% lang 'YesAutoCreateGuestAccounts' %}</label>
									<img onmouseout="HideHelp('guestcheckout2');" onmouseover="ShowHelp('guestcheckout2', '{% lang 'AutoCreateGuestAccounts' %}?', '{% lang 'AutoCreateGuestAccountsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
									<div style="display:none" id="guestcheckout2"></div>
								</div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<label for="EnableOrderComments">{% lang 'EnableOrderComments' %}?</label>
							</td>
							<td>
								<input type="checkbox" name="EnableOrderComments" id="EnableOrderComments" value="1" {{ IsEnableOrderComments|safe }} /> <label for="EnableOrderComments">{% lang 'YesEnableOrderComments' %}</label>
								<img onmouseout="HideHelp('OrderCommentsHelp');" onmouseover="ShowHelp('OrderCommentsHelp', '{% lang 'EnableOrderComments' %}?', '{% lang 'EnableOrderCommentsHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="OrderCommentsHelp"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<label for="EnableOrderComments">{% lang 'EnableOrderTermsAndConditions' %}?</label>
							</td>
							<td>
								<input onclick="$('.OrderTermsAndConditions').toggle();" type="checkbox" name="EnableOrderTermsAndConditions" id="EnableOrderTermsAndConditions" value="1" {{ IsEnableOrderTermsAndConditions|safe }} /> <label for="EnableOrderTermsAndConditions">{% lang 'YesEnableOrderTermsAndConditions' %}</label>
								<img onmouseout="HideHelp('EnableOrderTermsAndConditionsHelp');" onmouseover="ShowHelp('EnableOrderTermsAndConditionsHelp', '{% lang 'EnableOrderTermsAndConditions' %}?', '{% lang 'EnableOrderTermsAndConditionsHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="EnableOrderTermsAndConditionsHelp"></div>
								<div style="{{ HideOrderTermsAndConditions|safe }}" class="OrderTermsAndConditions">
									<table>
										<tr>
											<td valign="top"><img src="images/nodejoin.gif" alt="" /></td>
											<td>


												<input onclick="ToggleTermsAndConditions('link');" type="radio" name="OrderTermsAndConditionsType" id="TNCLink" class="OrderTermsAndConditionsType" value="link"  {{ IsEnableOrderTermsAndConditionsLink|safe }} />
												<label for="TNCLink">{% lang 'LinkToMyTermsAndConditions' %}:</label>
												<img onmouseout="HideHelp('OrderTermsAndConditionsLinkHelp');" onmouseover="ShowHelp('OrderTermsAndConditionsLinkHelp', '{% lang 'OrderTermsAndConditionsLink' %}?', '{% lang 'OrderTermsAndConditionsLinkHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
												<div style="display:none" id="OrderTermsAndConditionsLinkHelp"></div>
												<br />
												<input style="margin-left:25px; {{ HideOrderTermsAndConditionsLink|safe }}" class="Field250 OrderTermsAndConditionsLink" name="OrderTermsAndConditionsLink" value="{{ OrderTermsAndConditionsLink|safe }}">

											</td>
										</tr>
										<tr>
											<td valign="top"><img src="images/nodejoin.gif" alt="" /></td>
											<td>

												<input onclick="ToggleTermsAndConditions('textarea');" type="radio" name="OrderTermsAndConditionsType" id="TNCText" class="OrderTermsAndConditionsType" value="textarea"  {{ IsEnableOrderTermsAndConditionsTextarea|safe }} />
												<label for="TNCText">{% lang 'LetMeTypeTermsAndConditions' %}:</label>
												<img onmouseout="HideHelp('OrderTermsAndConditionsTextareaHelp');" onmouseover="ShowHelp('OrderTermsAndConditionsTextareaHelp', '{% lang 'OrderTermsAndConditionsText' %}?', '{% lang 'OrderTermsAndConditionsTextareaHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
												<div style="display:none" id="OrderTermsAndConditionsTextareaHelp"></div>
												<br />
												<textarea  style="margin-left:25px; {{ HideOrderTermsAndConditionsTextarea|safe }}" class="Field250 OrderTermsAndConditionsTextarea" name="OrderTermsAndConditionsTextarea" rows="5">{{ OrderTermsAndConditions|safe }}</textarea>

											</td>
										</tr>
									</table>
								</div>
							</td>
						</tr>
						<tr style="{{ HideMultiShipping|safe }}">
							<td class="FieldLabel">
								<label for="MultipleShippingAddresses">{% lang 'EnableMultipleShippingAddresses' %}:</label>
							</td>
							<td class="PanelBottom">
								<input type="checkbox" name="MultipleShippingAddresses" id="MultipleShippingAddresses" value="1" {{ IsMultipleShippingAddressesEnabled|safe }} /> <label for="MultipleShippingAddresses">{% lang 'YesEnableMultipleShippingAddresses' %}</label>
								<img onmouseout="HideHelp('MultipleShippingAddressesHelp');" onmouseover="ShowHelp('MultipleShippingAddressesHelp', '{% lang 'EnableMultipleShippingAddresses' %}?', '{% lang 'EnableMultipleShippingAddressesHelp' %}')" src="images/help.gif" width="24" height="16" border="0" />
								<div style="display:none" id="MultipleShippingAddressesHelp"></div>
							</td>
						</tr>
					</table>

					<table width="100%" class="Panel">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'CheckoutExtraFields' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel"><label for="CheckoutUseExtraFields">{% lang 'CheckoutUseExtraFieldsLabel' %}:</label></td>
							<td>
								<input type="checkbox" onclick="toggleCheckoutExtraFields();" name="CheckoutUseExtraFields" id="CheckoutUseExtraFields" {{ CheckoutUseExtraFieldsChecked|safe }}/> <label for="CheckoutUseExtraFields">{% lang 'CheckoutUseExtraFields' %}</label>
								<img onmouseout="HideHelp('cuef');" onmouseover="ShowHelp('cuef', '{% lang 'CheckoutUseExtraFields' %}', '{% lang 'CheckoutUseExtraFieldsHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="cuef"></div>
							</td>
						</tr>

						<tr style="{{ CheckoutExtraFieldRow1Display|safe }}" id="CheckoutExtraFieldRow1">
							<td class="FieldLabel">Activar campo 1? <input type="checkbox" onclick="toggleCheckoutExtraFieldActive(1);" name="CheckoutExtraFieldActive1" id="CheckoutExtraFieldActive1" {{ CheckoutExtraFieldActive1Checked|safe }}/></td>
							<td id="DivCheckoutExtraFieldActive1" style="{{ DivCheckoutExtraFieldActive1Display|safe }}">
								<table>
									<tr>
										<td>{% lang 'CheckoutExtraFieldName1' %} <input type="text" name="CheckoutExtraFieldName1" id="CheckoutExtraFieldName1" value="{{ CheckoutExtraFieldName1Value|safe }}" /></td>
										<td>Requerido? <input type="checkbox" name="CheckoutExtraFieldRequired1" id="CheckoutExtraFieldRequired1" {{ CheckoutExtraFieldRequired1Checked|safe }}/></td>
										<td>{% lang 'CheckoutExtraFieldType1' %}</td>
										<td>
											<select name="CheckoutExtraFieldType1">
												<option value="input"{{ CheckoutExtraFieldType1Selectedinput|safe }}>Texto (chico)</option>
												<option value="text"{{ CheckoutExtraFieldType1Selectedtext|safe }}>Texto (grande)</option>
												<option value="select"{{ CheckoutExtraFieldType1Selectedselect|safe }}>Seleccion</option>
												<option value="checkbox"{{ CheckoutExtraFieldType1Selectedcheckbox|safe }}>Check</option>
											</select>
										</td>
										<td>{% lang 'CheckoutExtraFieldValue1' %}</td>
										<td><input type="text" name="CheckoutExtraFieldValue1" id="CheckoutExtraFieldValue1" value="{{ CheckoutExtraFieldValue1Value|safe }}" /></td>
										<td>
											<img onmouseout="HideHelp('cuefv1');" onmouseover="ShowHelp('cuefv1', '{% lang 'CheckoutUseExtraFieldsValues' %}', '{% lang 'CheckoutUseExtraFieldsValuesHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
											<div style="display:none" id="cuefv1"></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>

						<tr style="{{ CheckoutExtraFieldRow2Display|safe }}" id="CheckoutExtraFieldRow2">
							<td class="FieldLabel">Activar campo 2? <input type="checkbox" onclick="toggleCheckoutExtraFieldActive(2);" name="CheckoutExtraFieldActive2" id="CheckoutExtraFieldActive2" {{ CheckoutExtraFieldActive2Checked|safe }}/></td>
							<td id="DivCheckoutExtraFieldActive2" style="{{ DivCheckoutExtraFieldActive2Display|safe }}">
								<table>
									<tr>
										<td>{% lang 'CheckoutExtraFieldName2' %} <input type="text" name="CheckoutExtraFieldName2" id="CheckoutExtraFieldName2" value="{{ CheckoutExtraFieldName2Value|safe }}" /></td>
										<td>Requerido? <input type="checkbox" name="CheckoutExtraFieldRequired2" id="CheckoutExtraFieldRequired2" {{ CheckoutExtraFieldRequired2Checked|safe }}/></td>
										<td>{% lang 'CheckoutExtraFieldType2' %}</td>
										<td>
											<select name="CheckoutExtraFieldType2">
												<option value="input"{{ CheckoutExtraFieldType2Selectedinput|safe }}>Texto (chico)</option>
												<option value="text"{{ CheckoutExtraFieldType2Selectedtext|safe }}>Texto (grande)</option>
												<option value="select"{{ CheckoutExtraFieldType2Selectedselect|safe }}>Seleccion</option>
												<option value="checkbox"{{ CheckoutExtraFieldType2Selectedcheckbox|safe }}>Check</option>
											</select>
										</td>
										<td>{% lang 'CheckoutExtraFieldValue2' %}</td>
										<td><input type="text" name="CheckoutExtraFieldValue2" id="CheckoutExtraFieldValue2" value="{{ CheckoutExtraFieldValue2Value|safe }}" /></td>
										<td>
											<img onmouseout="HideHelp('cuefv2');" onmouseover="ShowHelp('cuefv2', '{% lang 'CheckoutUseExtraFieldsValues' %}', '{% lang 'CheckoutUseExtraFieldsValuesHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
											<div style="display:none" id="cuefv2"></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						
						<tr style="{{ CheckoutExtraFieldRow3Display|safe }}" id="CheckoutExtraFieldRow3">
							<td class="FieldLabel">Activar campo 3? <input type="checkbox" onclick="toggleCheckoutExtraFieldActive(3);" name="CheckoutExtraFieldActive3" id="CheckoutExtraFieldActive3" {{ CheckoutExtraFieldActive3Checked|safe }}/></td>
							<td id="DivCheckoutExtraFieldActive3" style="{{ DivCheckoutExtraFieldActive3Display|safe }}">
								<table>
									<tr>
										<td>{% lang 'CheckoutExtraFieldName3' %} <input type="text" name="CheckoutExtraFieldName3" id="CheckoutExtraFieldName3" value="{{ CheckoutExtraFieldName3Value|safe }}" /></td>
										<td>Requerido? <input type="checkbox" name="CheckoutExtraFieldRequired3" id="CheckoutExtraFieldRequired3" {{ CheckoutExtraFieldRequired3Checked|safe }}/></td>
										<td>{% lang 'CheckoutExtraFieldType3' %}</td>
										<td>
											<select name="CheckoutExtraFieldType3">
												<option value="input"{{ CheckoutExtraFieldType3Selectedinput|safe }}>Texto (chico)</option>
												<option value="text"{{ CheckoutExtraFieldType3Selectedtext|safe }}>Texto (grande)</option>
												<option value="select"{{ CheckoutExtraFieldType3Selectedselect|safe }}>Seleccion</option>
												<option value="checkbox"{{ CheckoutExtraFieldType3Selectedcheckbox|safe }}>Check</option>
											</select>
										</td>
										<td>{% lang 'CheckoutExtraFieldValue3' %}</td>
										<td><input type="text" name="CheckoutExtraFieldValue3" id="CheckoutExtraFieldValue3" value="{{ CheckoutExtraFieldValue3Value|safe }}" /></td>
										<td>
											<img onmouseout="HideHelp('cuefv3');" onmouseover="ShowHelp('cuefv3', '{% lang 'CheckoutUseExtraFieldsValues' %}', '{% lang 'CheckoutUseExtraFieldsValuesHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
											<div style="display:none" id="cuefv3"></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						
						<tr style="{{ CheckoutExtraFieldRow4Display|safe }}" id="CheckoutExtraFieldRow4">
							<td class="FieldLabel">Activar campo 4? <input type="checkbox" onclick="toggleCheckoutExtraFieldActive(4);" name="CheckoutExtraFieldActive4" id="CheckoutExtraFieldActive4" {{ CheckoutExtraFieldActive4Checked|safe }}/></td>
							<td id="DivCheckoutExtraFieldActive4" style="{{ DivCheckoutExtraFieldActive4Display|safe }}">
								<table>
									<tr>
										<td>{% lang 'CheckoutExtraFieldName4' %} <input type="text" name="CheckoutExtraFieldName4" id="CheckoutExtraFieldName4" value="{{ CheckoutExtraFieldName4Value|safe }}" /></td>
										<td>Requerido? <input type="checkbox" name="CheckoutExtraFieldRequired4" id="CheckoutExtraFieldRequired4" {{ CheckoutExtraFieldRequired4Checked|safe }}/></td>
										<td>{% lang 'CheckoutExtraFieldType4' %}</td>
										<td>
											<select name="CheckoutExtraFieldType4">
												<option value="input"{{ CheckoutExtraFieldType4Selectedinput|safe }}>Texto (chico)</option>
												<option value="text"{{ CheckoutExtraFieldType4Selectedtext|safe }}>Texto (grande)</option>
												<option value="select"{{ CheckoutExtraFieldType4Selectedselect|safe }}>Seleccion</option>
												<option value="checkbox"{{ CheckoutExtraFieldType4Selectedcheckbox|safe }}>Check</option>
											</select>
										</td>
										<td>{% lang 'CheckoutExtraFieldValue4' %}</td>
										<td><input type="text" name="CheckoutExtraFieldValue4" id="CheckoutExtraFieldValue4" value="{{ CheckoutExtraFieldValue4Value|safe }}" /></td>
										<td>
											<img onmouseout="HideHelp('cuefv4');" onmouseover="ShowHelp('cuefv4', '{% lang 'CheckoutUseExtraFieldsValues' %}', '{% lang 'CheckoutUseExtraFieldsValuesHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
											<div style="display:none" id="cuefv4"></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						
						<tr style="{{ CheckoutExtraFieldRow5Display|safe }}" id="CheckoutExtraFieldRow5">
							<td class="FieldLabel">Activar campo 5? <input type="checkbox" onclick="toggleCheckoutExtraFieldActive(5);" name="CheckoutExtraFieldActive5" id="CheckoutExtraFieldActive5" {{ CheckoutExtraFieldActive5Checked|safe }}/></td>
							<td id="DivCheckoutExtraFieldActive5" style="{{ DivCheckoutExtraFieldActive5Display|safe }}">
								<table>
									<tr>
										<td>{% lang 'CheckoutExtraFieldName5' %} <input type="text" name="CheckoutExtraFieldName5" id="CheckoutExtraFieldName5" value="{{ CheckoutExtraFieldName5Value|safe }}" /></td>
										<td>Requerido? <input type="checkbox" name="CheckoutExtraFieldRequired5" id="CheckoutExtraFieldRequired5" {{ CheckoutExtraFieldRequired5Checked|safe }}/></td>
										<td>{% lang 'CheckoutExtraFieldType5' %}</td>
										<td>
											<select name="CheckoutExtraFieldType5">
												<option value="input"{{ CheckoutExtraFieldType5Selectedinput|safe }}>Texto (chico)</option>
												<option value="text"{{ CheckoutExtraFieldType5Selectedtext|safe }}>Texto (grande)</option>
												<option value="select"{{ CheckoutExtraFieldType5Selectedselect|safe }}>Seleccion</option>
												<option value="checkbox"{{ CheckoutExtraFieldType5Selectedcheckbox|safe }}>Check</option>
											</select>
										</td>
										<td>{% lang 'CheckoutExtraFieldValue5' %}</td>
										<td><input type="text" name="CheckoutExtraFieldValue5" id="CheckoutExtraFieldValue5" value="{{ CheckoutExtraFieldValue5Value|safe }}" /></td>
										<td>
											<img onmouseout="HideHelp('cuefv5');" onmouseover="ShowHelp('cuefv5', '{% lang 'CheckoutUseExtraFieldsValues' %}', '{% lang 'CheckoutUseExtraFieldsValuesHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
											<div style="display:none" id="cuefv5"></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>

					</table>

					<table width="100%" class="Panel">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'OrderSettings' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<label for="updateinventory">{% lang 'UpdateProductInventoryWhen' %}:</label>
							</td>
							<td class="PanelBottom">
								<select name="updateinventory" id="updateinventory" class="Field300">
									<option value="1" {{ UpdateInventorySuccessfulSelected|safe }}>{% lang 'UpdateInventorySuccessfulOrder' %}</option>
									<option value="2" {{ UpdateInventoryCompletedSelected|safe }}>{% lang 'UpdateInventoryOrderCompleted' %}</option>
								</select>
								<img onmouseout="HideHelp('ad1');" onmouseover="ShowHelp('ad1', '{% lang 'UpdateProductInventoryWhen' %}', '{% lang 'UpdateProductInventoryWhenHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="ad1"></div>
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<label for="UpdateInventoryOnOrderEdit">{% lang 'UpdateInventoryOnOrderEdit' %}</label>
							</td>
							<td class="PanelBottom">
								<input type="checkbox" name="UpdateInventoryOnOrderEdit" id="UpdateInventoryOnOrderEdit" value="1" {% if config.UpdateInventoryOnOrderEdit %}checked="checked"{% endif %} /> <label for="UpdateInventoryOnOrderEdit">{% lang 'YesUpdateInventoryOnOrderEdit' %}</label>
								{{ util.tooltip('UpdateInventoryOnOrderEdit', 'UpdateInventoryOnOrderEditHelp') }}
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<label for="UpdateInventoryOnOrderDelete">{% lang 'UpdateInventoryOnOrderDelete' %}</label>
							</td>
							<td class="PanelBottom">
								<input type="checkbox" name="UpdateInventoryOnOrderDelete" id="UpdateInventoryOnOrderDelete" value="1" {% if config.UpdateInventoryOnOrderDelete %}checked="checked"{% endif %} /> <label for="UpdateInventoryOnOrderDelete">{% lang 'YesUpdateInventoryOnOrderDelete' %}</label>
								{{ util.tooltip('UpdateInventoryOnOrderDelete', 'UpdateInventoryOnOrderDeleteHelp') }}
							</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<label for="orderstatusemails">{% lang 'EmailOnOrderStatusChange' %}:</label>
							</td>
							<td class="PanelBottom">
								<select name="orderstatusemails[]" id="orderstatusemails" class="Field300 ISSelectReplacement" size="11" multiple="multiple">
									{{ OrderStatusEmailList|safe }}
								</select>
								<img onmouseout="HideHelp('ad2');" onmouseover="ShowHelp('ad2', '{% lang 'EmailOnOrderStatusChange' %}', '{% lang 'EmailOnOrderStatusChangeHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display:none" id="ad2"></div>
							</td>
						</tr>
					</table>
					<table width="100%" class="Panel">
						<tr>
							<td class="Heading2" colspan="2">{% lang 'DigitalProductSettings' %}</td>
						</tr>
						<tr>
							<td class="FieldLabel">
								<label for="orderstatusemails">{% lang 'EnableDigitalHandlingFee' %}?</label>
							</td>
							<td class="PanelBottom">
								<label><input type="checkbox" onclick="$('.DigitalOrderHandling').toggle();" id="EnableDigitalOrderHandlingFee" name="EnableDigitalOrderHandlingFee" {{ DigitalOrderHandlingFeeChecked|safe }} /> {% lang 'YesEnableDigitalHandlingFee' %}</label>
								<img onmouseout="HideHelp('digitalhandling');" onmouseover="ShowHelp('digitalhandling', '{% lang 'EnableDigitalHandlingFee' %}?', '{% lang 'EnableDigitalHandlingFeeHelp' %}')" src="images/help.gif" width="24" height="16" border="0">
								<div style="display: none" id="digitalhandling"></div>
								<div style="{{ HideDigitalOrderHandlingFee|safe }}" class="DigitalOrderHandling">
									<table>
										<tr>
											<td><img src="images/nodejoin.gif" alt="" /></td>
											<td>
												Handling Fee:
												{{ LeftCurrencyToken|safe }}
												<input type="text" name="DigitalOrderHandlingFee" id="DigitalOrderHandlingFee" class="Field50" value="{{ DigitalOrderHandlingFee|safe }}" />
												{{ RightCurrencyToken|safe }}
											</td>
										</tr>
									</table>
								</div>
							</td>
						</tr>
					</table>
				</div>
				{{ CheckoutDivs|safe }}
				<table border="0" cellspacing="0" cellpadding="2" width="100%" class="PanelPlain" id="BottomButtons">
					<tr>
						<td width="200" class="FieldLabel">
							&nbsp;
						</td>
						<td>
							<input type="submit" value="{% lang 'Save' %}" class="FormButton SaveButton" />
							<input type="reset" value="{% lang 'Cancel' %}" class="FormButton CancelButton" onclick="ConfirmCancel()" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		</div>
	</form>

	<div id="ViewsMenu" class="DropShadow DropDownMenu" style="display: none; width:200px">
		<ul>
			{{ CheckoutFieldsOptions|safe }}
		</ul>
	</div>

	<script type="text/javascript">

		function ToggleTermsAndConditions(type)
		{
			if(type == 'link') {
				$('.OrderTermsAndConditionsLink').css({display: ''});
				$('.OrderTermsAndConditionsTextarea').css({display: 'none'});
			} else {
				$('.OrderTermsAndConditionsTextarea').css({display: ''});
				$('.OrderTermsAndConditionsLink').css({display: 'none'});
			}
		}

		function checkout_selected(checkout_id) {
			if(checkout_id == 'builtin') {
				if($('input[name=builtInGateway]:checked').val() == 1) {
					return true;
				}
				else {
					return false;
				}
			}

			if(g('checkoutproviders_old')) {
				var cp = g('checkoutproviders_old');
			}
			else {
				var cp = document.getElementById("checkoutproviders");
			}
			for(i = 0; i < cp.options.length; i++) {
				if(cp.options[i].value == checkout_id && cp.options[i].selected) {
					return true;
				}
			}
			return false;
		}

		function ShowTab(T)
		{
			i = 0;
			while (document.getElementById("tab" + i) != null) {
				document.getElementById("div" + i).style.display = "none";
				document.getElementById("tab" + i).className = "";
				i++;
			}

			document.getElementById("div" + T).style.display = "";
			document.getElementById("tab" + T).className = "active";
			document.getElementById("currentTab").value = T;
		}

		function CheckCheckoutSettingsForm()
		{
			if($('input[name=builtInGateway]:checked').val() == 1 && $('.BuiltInGatewayForm .MessageBoxError').length > 0) {
				alert('{% lang 'CannotEnableBuiltInModuleErrors' %}');
				return false;
			}

			if($('#EnableDigitalOrderHandlingFee').attr('checked')) {
				if($('#DigitalOrderHandlingFee').val() == '' || isNaN(priceFormat($('#DigitalOrderHandlingFee').val()))) {
					alert('{% lang 'EnterDigitalOrderHandlingFee' %}');
					$('#DigitalOrderHandlingFee').select();
					return false;
				}
			}

			if($('#EnableOrderTermsAndConditions').attr('checked')) {
				if($('.OrderTermsAndConditionsType:checked').val() == 'link') {
					if($('.OrderTermsAndConditionsLink').val() == '' || $('.OrderTermsAndConditionsLink').val() == 'http://') {
						alert("{% lang 'EnterTermsAndConditionsLink' %}");
						return false;
					}
				} else if($('.OrderTermsAndConditionsType:checked').val() == 'textarea') {
					if($('.OrderTermsAndConditionsTextarea').val() == '') {
						alert("{% lang 'EnterTermsAndConditions' %}");
						return false;
					}
				} else {
					alert("{% lang 'SelectTermsAndConditionsType' %}");
					return false;
				}
			}

			{{ CheckoutJavaScript|safe }}
		}

		function ConfirmCancel() {
			if(confirm('{% lang 'CancelCheckoutMessage' %}')) {
				document.location.href='index.php?ToDo=viewCheckoutSettings';
			}
			else {
				return false;
			}
		}

		function ToggleBuiltInGateway()
		{
			// Built in gateway is disabled
			if($('#BuiltInGatewayOption').css('display') == 'none') {
				return;
			}
			selected = $('input[name=builtInGateway]:checked').val();
			$('.builtInGateway').hide();
			$('.builtInGateway_'+selected).show();
		}

		$(document).ready(function() {
			$('input[name=builtInGateway]').click(function() {
				ToggleBuiltInGateway();
			});
			ToggleBuiltInGateway();

			ShowTab({{ CurrentTab|safe }});

		});

	</script>
