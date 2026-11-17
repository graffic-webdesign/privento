<?php
/**
 * Runs every tests/*Test.php. Exit code 1 if any assertion failed.
 */

require __DIR__ . '/bootstrap.php';

foreach ( glob( __DIR__ . '/*Test.php' ) as $file ) {
    echo basename( $file ) . "\n";
    t_reset();
    require $file;
}

echo $GLOBALS['t_failed'] ? "\n{$GLOBALS['t_failed']} FAILED\n" : "\nAll passed\n";
exit( $GLOBALS['t_failed'] ? 1 : 0 );
