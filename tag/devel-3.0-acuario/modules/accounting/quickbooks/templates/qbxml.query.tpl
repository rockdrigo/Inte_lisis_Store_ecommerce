<?xml version="1.0" encoding="utf-8"?>
<?qbxml version="{{ VersionNo|safe }}"?>
	<QBXML>
		<QBXMLMsgsRq onError="continueOnError">
			<{{ ServiceString|safe }}Rq>
				{{ XMLString|safe }}
			</{{ ServiceString|safe }}Rq>
		</QBXMLMsgsRq>
	</QBXML>