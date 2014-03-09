<link rel="stylesheet" type="text/css" href="Styles/ebay.css" />

<div id="ModalTitle">
	<span class="CategoryMachine_State_SelectCategory CategoryMachine_State_LoadMainCategories CategoryMachine_State_LoadMainCategoriesFailed" style="display:none;">
		{% lang 'SelectACategory' %} {% lang 'StepNumOfTotal' with ['step': 1, 'totalSteps': 2] %}
	</span>
	<span class="CategoryMachine_State_MapConditions CategoryMachine_State_LoadConditions CategoryMachine_State_LoadConditionsFailed CategoryMachine_State_NoConditions" style="display:none;">
		{% lang 'MapProductConditions' %} {% lang 'StepNumOfTotal' with ['step': 2, 'totalSteps': 2] %}
	</span>

	<span class="CategoryMachine_State_LoadCategoryFeatures CategoryMachine_State_LoadFeaturesFailed" style="display:none;">
		{% lang 'SelectACategory' %} {% lang 'StepNumOfTotal' with ['step': 2, 'totalSteps': 2] %}
	</span>
</div>

<div id="ModalContent">
	<div class="CategoryMachine_StateContainer">
		<div class="CategoryMachine_State_LoadMainCategories" style="display:none;">
			<br />
			{% lang 'LoadingEbayCategoriesMessage' %}<br />
		</div>

		<div class="CategoryMachine_State_LoadMainCategoriesFailed" style="display: none;">
			<br />
			{% lang 'LoadingEbayCategoriesFailure' %}<br />
			<br />
			{% lang 'TryAgainMessage' %}<br />
		</div>

		<div class="CategoryMachine_State_SelectCategory" style="display:none;">
			<p>
				{% lang 'SelectEbayCategoriesIntro' %}
			</p>
			<div id="selectCategoryMessage"></div>
			<div id="categoriesContainer">
				<table>
					<tr id="categoriesRow">
					</tr>
				</table>
			</div>
		</div>

		<div class="CategoryMachine_State_LoadConditions" style="display: none;">
			<br />
			{% lang 'LoadingEbayCategoryCondMessage' %}<br />
		</div>

		<div class="CategoryMachine_State_LoadConditionsFailed" style="display: none;">
			<br />
			{% lang 'LoadingEbayCategoryCondFailure' %}<br />
			<br />
			{% lang 'MayTryAgainMessage' %}<br />
		</div>

		<div class="CategoryMachine_State_MapConditions" style="display:none;">
			<p id="conditionsIntro"></p>
			<div id="mapConditionsMessage"></div>
			<div id="categoryConditions"></div>
		</div>

		<div class="CategoryMachine_State_NoConditions" style="display: none;">
			<br />
			{% lang 'NoCondSupported' %}<br />
			<br />
			{% lang 'FinishToAddEbayCategory' %}<br />
		</div>

		<div class="CategoryMachine_State_LoadCategoryFeatures" style="display: none;">
			<br />
			{% lang 'LoadingEbayCategoryDetailsMessage' %}<br />
		</div>

		<div class="CategoryMachine_State_LoadFeaturesFailed" style="display: none;">
			<br />
			{% lang 'LoadingEbayCategoryDetailsFailure' %}<br />
			<br />
			{% lang 'MayTryAgainMessage' %}<br />
		</div>
	</div>
</div>

<div class="ModalButtonRow CategoryMachine_ButtonRow">
	<div style="float:left;">
		<button class="CategoryMachine_CancelButton CancelButton" disabled="disabled" accesskey="c">{% lang 'Cancel' %}</button>
	</div>
	<div style="float:right">
		<button class="CategoryMachine_BackButton" disabled="disabled" accesskey="b">&lt; {% lang 'Back' %}</button>
		<button class="CategoryMachine_NextButton" disabled="disabled" accesskey="n">{% lang 'Next' %} &gt;</button>
		<button class="CategoryMachine_FinishButton" style="display:none;" accesskey="f">{% lang 'Finish' %}</button>
	</div>
	<br clear="both" />
</div>
