<div id="categorySelectModal" style="display: none;">
	<div class="ModalTitle"></div>
	<div class="ModalContent">
		<p class="intro"></p>
		<div class="message"></div>
		<div class="CategoriesContainer">
			<table>
				<tr class='CategoriesRow'>
					<td>
					    <div class="CategoryBox ISSelect" style="visibility:hidden">
					        <ul>
									</ul>
							</div>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<div class="ModalButtonRow">
		<div class="FloatLeft">
			<input type="button" class="cancel FormButton" value="{% lang 'Cancel' %}"/>
		</div>
			<input type="button" class="save" value="{% lang 'ChooseThisCategory' %}"/>
	</div>
</div>