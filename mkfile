MKSHELL=rc

caStem = `{printf %s-%s-CA `{whoami} `{hostname}}

all:VQ: installed-ca installed-server-cert
	echo ALL DONE.
	echo Please remember to restart the HTTP server, for example, as root:
	echo '	'apachectl restart
	echo Please remember to update CA certificates, for exampl, as root:
	echo '	'update-ca-certificates
	echo Please remember to add the certificate as ''trusted'' to your browser.

installed-ca:VQ: /usr/local/share/ca-certificates/$caStem.crt

CA/$caStem.pem:Q: CA/$caStem.conf CA/$caStem.key
	mkdir -p `{dirname $target}
	openssl req -x509 -new -nodes -key CA/$caStem.key -sha256 -days 1024 -out $target -config CA/$caStem.conf

CA/$caStem.conf:Q: general.conf
	mkdir -p `{dirname $target}
	cat $prereq > $target

CA/$caStem.key:Q: general.conf
	mkdir -p `{dirname $target}
	openssl genrsa -out $target 4096

general.conf:Q: skeleton-general.conf
	if (grep 'C = #Country Name' general.conf > /dev/null) {
		echo Please fill in the general.conf file
		exit 1}
	if not
		true

skeleton-general.conf:VQ:
	if (test -e general.conf)
		exit 0
	cat > general.conf <<EOS
	[req]
	default_bits = 4096
	prompt = no
	default_md = sha256
	distinguished_name = dn
	
	[dn]
	C = #Country Name (2 letter code) [AU]
	ST = #State or Province Name (full name) [Some-State]
	L = #Locality Name (eg, city) []: 
	O = #Organization Name (eg, company) [Internet Widgits Pty Ltd]
	OU = #Organizational Unit Name (eg, section) []
	CN = #%O local dev CA by %USERNAME
	emailAddress = #email address
	EOS
	echo I have created a skeleton general.conf file.

/usr/local/share/ca-certificates/$caStem.crt:VQ: CA/$caStem.pem
	# dereferences the symlink; i.e., test if the symlink points there
	if (test $target -ef $prereq)
		echo Checking target $target: OK
	if not {
		echo NEED A SYMLINK AT $target POITING TO `{realpath $prereq}
		echo as root run:
		echo '	'mkdir -p `{dirname $target}';' ln -s `{realpath $prereq} $target
		exit 1 }

installed-server-cert:VQ: installed-server-cert-file installed-server-key-file

installed-server-cert-file:VQ: /etc/httpd/server.crt
installed-server-key-file:VQ: /etc/httpd/server.key

/etc/httpd/server.crt:Q: cert/server.pem
	# dereferences the symlink; i.e., test if the symlink points there
	if (test $target -ef $prereq)
		echo Checking target $target: OK
	if not {
		echo NEED A SYMLINK AT $target POITING TO `{realpath $prereq}
		echo as root run:
		echo '	'mkdir -p `{dirname $target}';' ln -s `{realpath $prereq} $target
		exit 1 }

/etc/httpd/server.key:Q: cert/server.key
	# dereferences the symlink; i.e., test if the symlink points there
	if (test $target -ef $prereq)
		echo Checking target $target: OK
	if not {
		echo NEED A SYMLINK AT $target POITING TO `{realpath $prereq}
		echo as root run:
		echo '	'mkdir -p `{dirname $target}';' ln -s `{realpath $prereq} $target
		exit 1 }

server-cert-file:V: /etc/httpd/server.crt

cert/server.key:Q: cert/server.csr
	true

cert/server.pem:Q: cert/server.csr CA/$caStem.pem CA/$caStem.key cert/server.v3.ext
	mkdir -p `{dirname $target}
	openssl x509 -req -in $prereq(1) -CA $prereq(2) -CAkey $prereq(3) -CAcreateserial -out $target -days 500 -sha256 -extfile $prereq(4)

cert/server.csr:Q: cert/server.conf
	mkdir -p `{dirname $target}
	openssl req -new -sha256 -nodes -out $target -newkey rsa:4096 -keyout cert/server.key -config $prereq

cert/server.conf:Q: general.conf
	mkdir -p `{dirname $target}
	9 awk < $prereq > $target \
	'
		/^CN[ ]*=/ {
			print "CN = localhost"
			next
		}
		{
			print $0
		}
	'

cert/server.v3.ext:Q: cert/domains.conf
	cat > $target <<'EOS'
	authorityKeyIdentifier=keyid,issuer
	basicConstraints=CA:FALSE
	keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
	subjectAltName = @alt_names
	
	[alt_names]
	EOS
	cat >> $target < $prereq

cert/domains.conf:Q: domains.list
	mkdir -p `{dirname $target}
	9 awk '
		BEGIN { n = 1 }
		/^[^#]/ {
			print "DNS." n " = " $0
			n = n+1
			print "DNS." n " = www." $0
		} ' < $prereq > $target

domains.list:VQ: skeleton-domains.list
	if (9 grep '^#example.com' domains.list > /dev/null) {
		echo Please fill in the domains.list file
		exit 1}
	if not
		true

skeleton-domains.list:VQ:
	if (test -e domains.list)
		exit 0
	cat > domains.list <<EOS
	#example.com
	#example.net
	#example.org
	EOS
	echo I have created a skeleton domains.list file.


