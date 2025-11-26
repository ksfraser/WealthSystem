# Copyright 1999-2006 Gentoo Foundation
# Distributed under the terms of the GNU General Public License v2
# $Header: $

DESCRIPTION="TA-Lib provides common functions for the technical analysis of stock/future/commodity market data."
HOMEPAGE="http://ta-lib.org/"
SRC_URI="mirror://sourceforge/ta-lib/${P}-src.tar.gz"
LICENSE="BSD"
SLOT="0"
KEYWORDS="x86 amd64"
IUSE=""
DEPEND=""
S=${WORKDIR}/${PN}/c/make/csr/linux/g++

src_compile()
{
	export MAKEOPTS=""
	emake || die "emake failed"
}

src_install()
{
	mkdir -p ${D}/usr/include/ta-lib || die
	mkdir -p ${D}/usr/lib/ta-lib || die
	cd ${WORKDIR}/${PN}/c/include || die
	cp * ${D}/usr/include/ta-lib/ || die
	cd ${WORKDIR}/${PN}/c/lib || die
	cp * ${D}/usr/lib/ta-lib/ || die
}
