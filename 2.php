<?php
$m = new MongoClient( 'mongodb://127.0.0.1:27017');
$c = $m->WakePHP->selectCollection( 'jobqueue' );
$cursor = $c->find( array('status' => 'v' ));
$cursor->sort(['$natural' => 1]);
$cursor->tailable( true );
$cursor->awaitData( true );

while (true) {
    if (!$cursor->hasNext()) {
        // we've read all the results, exit
        if ($cursor->dead()) {
            break;
        }
    } else {
        var_dump( $cursor->getNext() );
    }
}