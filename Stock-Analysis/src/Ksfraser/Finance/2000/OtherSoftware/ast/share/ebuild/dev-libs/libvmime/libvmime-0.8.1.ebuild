# Copyright 1999-2007 Gentoo Foundation
# Distributed under the terms of the GNU General Public License v2
# $Header: $

inherit eutils

DESCRIPTION="VMime is a powerful C++ class library for working with MIME messages and Internet messaging services like IMAP, POP or SMTP."
HOMEPAGE="http://www.vmime.org"
SRC_URI="mirror://sourceforge/vmime/${P}.tar.bz2"

LICENSE="GPL-2"
SLOT="0"
KEYWORDS="~amd64 ~x86"
IUSE="sasl gnutls"

DEPEND="virtual/libiconv
        tls? (net-libs/gnutls)
        sasl? (libgsasl)"
RDEPEND=""

src_unpack() {
	unpack ${A} && cd "${S}"
}

src_compile() {
	econf --enable-platform-posix \
	       $(use_enable tls) \
	       $(use_enable sasl) \
		--enable-static \
		--enable-shared || die
	emake || die "make failure"
}

src_install() {
    dodir /usr/{bin,include,lib}
    emake install DESTDIR="${D}" || die "install failure"
    
    dodoc AUTHORS INSTALL NEWS README TODO THANKS ChangeLog
    dodoc examples/*
}
