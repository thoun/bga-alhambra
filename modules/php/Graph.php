<?php
namespace ALH;

 /**
  * Graph.php
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


class Graph
{
  protected $net;
	function __construct($net)
	{
    $this->net = $net;
	}

  // Return the longest path of this net
  // !! The length of the path is the number of node in the path. For a single bar with 2 nodes, length is 2 !
  function longestPath()
  {
    $result = [];
    foreach(array_keys($this->net) as $node){
      $path = $this->longestPathFrom($node);

      if(count($path) > count($result))
        $result = $path;
    }

    return $result;
  }

  // Return the length and the path of the longest path of this net starting from this node
  function longestPathFrom($start)
  {
    return $this->longestPathFromAux([$start]);
  }

  // Return the longest path of this net starting with the sequence of
  // nodes specified in "path" (path = sorted array of node_id)
  protected function longestPathFromAux($path)
  {
    $result = $path;
    $endNode = end($path);
    foreach($this->net[$endNode] as $nextNode){
      if(in_array($nextNode, $path))
        continue; // Already in the path

      $newPath = $this->longestPathFromAux(array_merge($path, [$nextNode]));
      if(count($newPath) > count($result))
        $result = $newPath;
    }

    return $result;
  }


  // Return true if the network is connex (all nodes are linked in one piece)
  function isConnex()
  {
    if(count($this->net) == 0)
      return true;

    $explored = [];    // We already explored all node linked linked of this one
    $stack = [];  // Our next node to work on

    reset($this->net);
    $stack[] = key($this->net);
    while(count($stack) > 0){
      $node = array_pop($stack);
      $explored[] = $node;

      foreach($this->net[$node] as $neighbour){
        if(!in_array( $neighbour, $explored) && ! in_array($neighbour, $stack))
          $stack[] = $neighbour;
      }
    }

    return count($explored) == count($this->net);
  }
}
