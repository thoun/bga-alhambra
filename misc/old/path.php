<?php
 /**
  * path.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * Pathfinding
  *
  */

/*
A "net" structure is a graph described by:
_ nodes with IDs
_ paths between nodes

In term of data structure:
$net = array(
    <node 1> => array( <IDs of nodes links with node 1>,
    ...
    <node N> => array( <IDs of nodes links with node N>
)


*/


class Path extends APP_GameClass
{
	function __construct( )
	{

	}

    // Return the length and the path of the longest path of this net
    // !! The length of the path is the number of node in the path. For a single bar with 2 nodes, length is 2 !
    function longest( $net )
    {
        $result = array( "l" => 0, "p" => array() );

        foreach( $net as $node_id => $links )
        {
            $inter = self::longest_from( $net, $node_id );

            if( $inter['l'] > $result['l'] )
                $result = $inter;
        }
        return $result;
    }

    // Return the length and the path of the longest path of this net starting from this node
    function longest_from( $net, $start_id )
    {
        return self::longest_from_path( $net, array( $start_id ) );
    }

    // Return the length and the path of the longest path of this net starting with the sequence of
    // nodes specified in "path" (path = sorted array of node_id)
    function longest_from_path( $net, $path )
    {
//        echo "\nExploring path: ".implode( ',', $path )."\n";

        $result = array( 'l' => count( $path ),   // Minimum result = current path length
                         'p' => $path );

        $last_node = end( $path );
        foreach( $net[ $last_node ] as $next_node )
        {
//            echo "\ncontinue with $next_node ? ";
            if( ! in_array( $next_node, $path ) )   // Is the next node is not already in the path ?
            {
                $new_path = $path;
                $new_path[] = $next_node;

                $inter = self::longest_from_path( $net, $new_path );

                if( $inter['l'] > $result['l'] )
                    $result = $inter;
             }
//             else
//                echo "already in the path";
        }

        return $result;
    }

    // Return true if the network is connex (all nodes are linked in one piece)
    function is_connex( $net )
    {
        if( count( $net ) == 0 )
            return true;

        $explored = array();    // We already explored all node linked linked of this one
        $to_explore = array();  // Our next node to work on

        reset( $net );
        $to_explore[] = key( $net );

        while( count( $to_explore ) > 0 )
        {
            $node = array_pop( $to_explore );
          //  echo "exploring $node\n";
            $explored[] = $node;

            $neighbours = $net[ $node ];
            foreach( $neighbours as $neighbour )
            {
                if( ! in_array( $neighbour, $explored ) && ! in_array( $neighbour, $to_explore ) )
                    $to_explore[] = $neighbour;
            }
        }

        if( count( $explored ) == count( $net ) )
            return true;
        else
            return false;
    }

}
