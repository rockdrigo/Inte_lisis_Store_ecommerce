PRIVATEKEY=isc_privatekey.pem
PRIVATEKEYPASS={{ PrivateKeyPass|safe }}
PRIVATECERT=isc_privatecert.crt
CERTIFICATE0=rabo.cer
CERTIFICATE1=ing.cer
ACQUIRERURL={{ AcquirerURL|safe }}
ACQUIRERTIMEOUT=10
#enter your merchant id below
MERCHANTID={{ MerchantID|safe }}
SUBID=0
#enter payment confirmation url
MERCHANTRETURNURL={{ ReturnURL|safe }}
#5 minute payment expiry
EXPIRATIONPERIOD=PT5M
#enter the path to the logfile below
LOGFILE=Connector_log.txt
TraceLevel = ERROR
#PROXY=
#PROXYACQURL=