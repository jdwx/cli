<?php


declare( strict_types = 1 );


# This file intentionally declares no class. It exists so that
# BaseInterpreter::addCommandDirectory() encounters a .php file whose
# expected class cannot be loaded, exercising the class_exists() skip path.
