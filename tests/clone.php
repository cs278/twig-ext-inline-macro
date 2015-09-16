$for = new \Twig_Node_For(
    new \Twig_Node_Expression_AssignName('_key', 0),
    new \Twig_Node_Expression_AssignName('i', 0),
    new \Twig_Node_Expression_Array([new \Twig_Node_Expression_Constant(1, 0), new \Twig_Node_Expression_Constant(2, 0)], 0),
    null,
    new \Twig_Node([]),
    null,
    0
);

var_dump(md5(spl_object_hash($for)));
// debug_zval_dump($for);
var_dump($for->getNode('body')->getNode(1));
var_dump($for->getloop());



$for = $this->cloneNode($for);

var_dump(md5(spl_object_hash($for)));
// debug_zval_dump($for);
var_dump($for->getNode('body')->getNode(1));
var_dump($for->getloop());

die;
