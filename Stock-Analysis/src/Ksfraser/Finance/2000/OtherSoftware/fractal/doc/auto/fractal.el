(TeX-add-style-hook "fractal"
 (function
  (lambda ()
    (LaTeX-add-bibliographies
     "bibliography")
    (TeX-add-symbols
     '("subidx" 2)
     '("idx" 1))
    (TeX-run-style-hooks
     "latex2"
     "newreport10"
     "newreport"
     "times"
     "rcs"
     "fancyheadings"
     "gnuindex"
     "epsf"
     "title"
     "abstract"
     "preface"
     "chap1"
     "chap2"
     "chap3"
     "chap4"
     "appa"
     "appb"
     "appc"
     "appd"
     "fractal"
     "colophon"))))

