<script language="javascript" type="text/javascript">//<![CDATA[
if (typeof lang == 'undefined') { lang = {}; }
lang.ConfirmCancel = "{% lang 'ConfirmCancel' %}";
//]]></script>
<div class="BodyContainer" style="margin-top: 0;">
	<div style="{{ HideGettingStarted|safe }}" class="DashboardGettingStarted">
		{{ GettingStarted|safe }}
	</div>
	<div style="{{ HideOverview|safe }}" class="DashboardCommonTasks">
		<div class="Heading1">{% lang 'Home' %}</div>
		{{ Messages|safe }}
		<div class="DashboardRightColumn">
			<div class="DashboardPanel DashboardPanelCurrentNotifications" style="{{ HideNotificationsList|safe }}">
				<div class="DashboardPanelContent">
					<h3>{% lang 'PendingItems' %}</h3>
					<ul>
						{{ NotificationsList|safe }}
					</ul>
				</div>
			</div>

			<div class="DashboardPanel DashboardPanelPerformanceIndicators" id="DashboardPanelPerformanceIndicators" style="{{ HideDashboardPerformanceIndcators|safe }}">
				<div class="DashboardPanelContent">
					<div class="DashboardPillMenu DashboardPerformanceIndicatorsPeriodButton">
						<div class="DashboardPillMenuStart"></div>
						<div class="DashboardPillMenuEnd"></div>
						<span class="Label">
							{% lang 'View' %}:
						</span>
						<span class="Buttons">
							<a href="#" class="{{ PerformanceIndicatorsActiveDay|safe }}" rel="period=day">{% lang 'Day' %}</a>
							<a href="#" class="{{ PerformanceIndicatorsActiveWeek|safe }}" rel="period=week">{% lang 'Week' %}</a>
							<a href="#" class="{{ PerformanceIndicatorsActiveMonth|safe }}" rel="period=month">{% lang 'Month' %}</a>
							<a href="#" class="{{ PerformanceIndicatorsActiveYear|safe }} Last" rel="period=year">{% lang 'Year' %}</a>
						</span>
					</div>
					<h3>{% lang 'StoreSnapshot' %}</h3>
					<div id="DashboardPerformanceIndicators">
						{{ PerformanceIndicatorsTable|safe }}
					</div>
				</div>
			</div>

			<div class="DashboardPanel DashboardPanelOrderBreakdown" style="{{ HideDashboardBreakdownGraph|safe }}">
				<div class="DashboardPanelContent">
					<span class="DashboardActionButton DashboardOrderBreakdownAllStatsButton">
						<a href="index.php?ToDo=viewStats">
							<span class="ButtonArrow"></span>
							<span class="ButtonText ButtonTextWithArrow">{% lang 'ViewAllStatistics' %}</span>
						</a>
					</span>
					<h3>{% lang 'OrderBreakDown' %} <small>({% lang 'Last7Days' %})</small></h3>
					<ul class="OrderBreakdownChart">
						{{ DashboardBreakdownGraph|safe }}
					</ul>
					<div class="OrderBreakdownChartKey">
						<div class="First">{{ GraphSeriesLabel0|safe }}</div>
						<div>{{ GraphSeriesLabel1|safe }}</div>
						<div>{{ GraphSeriesLabel2|safe }}</div>
						<div>{{ GraphSeriesLabel3|safe }}</div>
						<div class="Last">{{ GraphSeriesLabel4|safe }}</div>
					</div>
				</div>
			</div>
			<div class="DashboardPanel DashboardPanelHelpArticles" style="{{ HidePopularHelpArticles|safe }}">
				<div class="DashboardPanelContent" style="overflow: auto">
					<form action="{{ SearchKnowledgeBaseUrl|safe }}" method="post" class="DashboardHelpArticlesSearchForm" style="{{ HideSearchKnowledgeBase|safe }}">
						<input type="text" name="q" class="DashboardHelpSearchQuery DashboardHelpSearchHasImage" />
						<span class="DashboardActionButton">
							<a href="#">
								<span class="ButtonText">{% lang 'Go' %}</span>
							</a>
						</span>
					</form>
					<h3>{% lang 'PopularHelpArticles' %}</h3>
					<div class="HelpArticlesList">
						<img src="images/ajax-loader.gif" alt="" />
					</div>
					<span class="DashboardActionButton DashboardBrowseAllHelpArticlesButton">
						<a href="{{ ViewKnowledgeBaseLink|safe }}">
							<span class="ButtonArrow"></span>
							<span class="ButtonText ButtonTextWithArrow">{% lang 'ViewKnowledgeBase' %}</span>
						</a>
					</span>
				</div>
			</div>
		</div>
		<div class="DashboardLeftColumn">

			{{ TrialExpiryMessage|safe }}

			{{ VersionCheckMessage|safe }}

			<div class="DashboardPanel DashboardPanelFeatured DashboardPanelOverview">
				<div class="DashboardPanelContent">
					<div class="DashboardWhatsNext">
						<a href="#" class="ToggleLink GettingStartedToggleLink" style="{{ HideToggleGettingStartedAtGlance|safe }}">{% lang 'SwitchToGettingStarted' %}</a>
						<h3>{% lang 'WhatsNext' %}</h3>
						<a href="index.php?ToDo=viewOrders" class="DashboardWhatsNextLink DashboardWhatsNextManageOrders" style="{{ HideManageOrdersLink|safe }}">
							<span>{% lang 'NextManageOrders' %}</span>
						</a>
						<a href="index.php?ToDo=addProduct" class="DashboardWhatsNextLink DashboardWhatsNextAddAProduct" style="{{ HideAddProductLink|safe }}">
							<span>{% lang 'NextAddAProduct' %}</span>
						</a>
						<br class="ClearLeft" />
					</div>
					<div class="DashboardAtAGlance" style="{{ HideAtAGlance|safe }}">
						<h3>{% lang 'YourStoreAtAGlance' %}</h3>
						<ul>
							{{ AtGlanceItems|safe }}
						</ul>
						<br class="ClearLeft" />
					</div>
				</div>
			</div>
			<div class="DashboardPanel DashboardPanelRecentOrders" style="{{ HideRecentOrders|safe }}">
				<div class="DashboardPanelContent">
					<span class="DashboardActionButton DashboardRecentOrdersAllButton">
						<a href="index.php?ToDo=viewOrders">
							<span class="ButtonArrow"></span>
							<span class="ButtonText ButtonTextWithArrow">{% lang 'ViewAllOrders' %}</span>
						</a>
					</span>
					<h3><span id="DashboardRecentOrdersTitle">{% lang 'RecentOrders' %}</span> {% lang 'LowerOn' %} {{ StoreName|safe }}</h3>
					<div class="DashboardFilterOptions" style="margin-top: 18px;">
						<div>
							{% lang 'Show' %}:
						</div>
						<ul class="DashboardRecentOrdersToggle">
							<li class="{{ RecentOrdersActiveRecentClass|safe }}"><a href="#" rel="status=recent">{% lang 'RecentOrders' %}</a></li>
							<li class="{{ RecentOrdersActivePendingClass|safe }}"><a href="#" rel="status=pending">{% lang 'PendingOrders' %}</a></li>
							<li class="{{ RecentOrdersActiveCompletedClass|safe }}"><a href="#" rel="status=completed">{% lang 'CompletedOrders' %}</a></li>
							<li class="{{ RecentOrdersActiveRefundedClass|safe }}"><a href="#" rel="status=refunded">{% lang 'RefundedOrders' %}</a></li>
						</ul>
						<br style="clear: left;" />
					</div>
					<ul style="clear: left" class="DashboardRecentOrderList">
						{{ RecentOrdersList|safe }}
					</ul>
				</div>
			</div>
		</div>
	</div>
	<br class="Clear" />
</div>
