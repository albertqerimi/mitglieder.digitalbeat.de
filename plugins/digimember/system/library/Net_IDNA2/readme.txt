

Downloaded from: http://pear.php.net/package/Net_IDNA2/download


To update repeat the changes I made:

(*) remove require statements for exceptions:

    require_once 'IDNA2/Exception.php';
    require_once 'IDNA2/Exception/Nameprep.php';

(*) rename main class in IDNA2.php from Net_IDNA2 to ncore_Net_IDNA2

(*) in IDNA2.php replace Net_IDNA2:: by ncore_Net_IDNA2::

(*) replace Exception class name
        Net_IDNA2_Exception_Nameprep
    by
        InvalidArgumentException


Christian Neise, Octber 12th 2014


