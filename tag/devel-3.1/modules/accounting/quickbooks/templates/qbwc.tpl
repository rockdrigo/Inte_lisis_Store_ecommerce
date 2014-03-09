<?xml version="1.0"?>
	<QBWCXML>
		<AppID></AppID>
		<AppName>{{ AppName|safe }}</AppName>
		<AppURL>{{ AppURL|safe }}</AppURL>
		<CertURL>{{ CertURL|safe }}</CertURL>
		<AppDescription>{{ AppDescription|safe }}</AppDescription>
		<AppSupport>{{ AppSupportURL|safe }}</AppSupport>
		<UserName>{{ Username|safe }}</UserName>
		<OwnerID>{{ OwnerID|safe }}</OwnerID>
		<FileID>{{ FileID|safe }}</FileID>
		<QBType>{{ QBType|safe }}</QBType>
		<IsReadOnly>false</IsReadOnly>
		{{ Scheduler|safe }}
	</QBWCXML>
