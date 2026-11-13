<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mensajes de validación (español rioplatense)
|--------------------------------------------------------------------------
|
| Los usa toda regla que no tenga mensaje propio en su FormRequest. Van en
| las palabras del cliente, sin jerga: "El campo mail es obligatorio.".
| `attributes` traduce los nombres técnicos de los campos (incluidos los
| anidados con `*`) y `values` los valores que aparecen en :other/:values.
| tests/Feature/Shared/TraduccionesDeValidacionTest.php verifica que cada regla
| que usan los FormRequest tenga su mensaje acá.
|
*/

return [

    'accepted' => 'Tenés que aceptar el campo :attribute.',
    'accepted_if' => 'Tenés que aceptar el campo :attribute cuando :other es :value.',
    'active_url' => 'El campo :attribute tiene que ser un link válido.',
    'after' => 'El campo :attribute tiene que ser una fecha posterior al :date.',
    'after_or_equal' => 'El campo :attribute tiene que ser una fecha igual o posterior al :date.',
    'alpha' => 'El campo :attribute solo puede tener letras.',
    'alpha_dash' => 'El campo :attribute solo puede tener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute solo puede tener letras y números.',
    'any_of' => 'El campo :attribute no es válido.',
    'array' => 'El campo :attribute tiene que ser una lista.',
    'array_keys' => 'El campo :attribute solo puede tener estos datos: :values.',
    'ascii' => 'El campo :attribute solo puede tener letras, números y símbolos comunes.',
    'base64' => 'El campo :attribute no tiene un formato válido.',
    'before' => 'El campo :attribute tiene que ser una fecha anterior al :date.',
    'before_or_equal' => 'El campo :attribute tiene que ser una fecha igual o anterior al :date.',
    'between' => [
        'array' => 'El campo :attribute tiene que tener entre :min y :max elementos.',
        'file' => 'El archivo :attribute tiene que pesar entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute tiene que estar entre :min y :max.',
        'string' => 'El campo :attribute tiene que tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute tiene que ser sí o no.',
    'can' => 'El campo :attribute tiene un valor que no podés usar.',
    'confirmed' => 'La repetición del campo :attribute no coincide.',
    'contains' => 'Al campo :attribute le falta un valor obligatorio.',
    'current_password' => 'La clave no es correcta.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_equals' => 'El campo :attribute tiene que ser la fecha :date.',
    'date_format' => 'El campo :attribute tiene que tener el formato :format.',
    'decimal' => 'El campo :attribute tiene que tener :decimal decimales.',
    'declined' => 'El campo :attribute tiene que quedar rechazado.',
    'declined_if' => 'El campo :attribute tiene que quedar rechazado cuando :other es :value.',
    'different' => 'Los campos :attribute y :other tienen que ser distintos.',
    'digits' => 'El campo :attribute tiene que tener :digits dígitos.',
    'digits_between' => 'El campo :attribute tiene que tener entre :min y :max dígitos.',
    'dimensions' => 'La imagen :attribute no tiene un tamaño válido.',
    'distinct' => 'El campo :attribute está repetido.',
    'doesnt_contain' => 'El campo :attribute no puede tener ninguno de estos valores: :values.',
    'doesnt_end_with' => 'El campo :attribute no puede terminar con: :values.',
    'doesnt_start_with' => 'El campo :attribute no puede empezar con: :values.',
    'email' => 'El campo :attribute no es un mail válido.',
    'encoding' => 'El campo :attribute tiene que estar codificado en :encoding.',
    'ends_with' => 'El campo :attribute tiene que terminar con: :values.',
    'enum' => 'El valor elegido en :attribute no es válido.',
    'exists' => 'El valor elegido en :attribute no existe o no está disponible.',
    'extensions' => 'El archivo :attribute tiene que ser de tipo: :values.',
    'file' => 'El campo :attribute tiene que ser un archivo.',
    'filled' => 'El campo :attribute no puede quedar vacío.',
    'gt' => [
        'array' => 'El campo :attribute tiene que tener más de :value elementos.',
        'file' => 'El archivo :attribute tiene que pesar más de :value kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser mayor que :value.',
        'string' => 'El campo :attribute tiene que tener más de :value caracteres.',
    ],
    'gte' => [
        'array' => 'El campo :attribute tiene que tener :value elementos o más.',
        'file' => 'El archivo :attribute tiene que pesar :value kilobytes o más.',
        'numeric' => 'El campo :attribute tiene que ser mayor o igual que :value.',
        'string' => 'El campo :attribute tiene que tener :value caracteres o más.',
    ],
    'hex_color' => 'El campo :attribute tiene que ser un color válido.',
    'image' => 'El campo :attribute tiene que ser una imagen.',
    'in' => 'El valor elegido en :attribute no es válido.',
    'in_array' => 'El campo :attribute tiene que estar en :other.',
    'in_array_keys' => 'El campo :attribute tiene que tener al menos uno de estos datos: :values.',
    'integer' => 'El campo :attribute tiene que ser un número entero.',
    'ip' => 'El campo :attribute tiene que ser una dirección IP válida.',
    'ipv4' => 'El campo :attribute tiene que ser una dirección IPv4 válida.',
    'ipv6' => 'El campo :attribute tiene que ser una dirección IPv6 válida.',
    'json' => 'El campo :attribute no tiene un formato válido.',
    'list' => 'El campo :attribute tiene que ser una lista.',
    'lowercase' => 'El campo :attribute tiene que estar en minúsculas.',
    'lt' => [
        'array' => 'El campo :attribute tiene que tener menos de :value elementos.',
        'file' => 'El archivo :attribute tiene que pesar menos de :value kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser menor que :value.',
        'string' => 'El campo :attribute tiene que tener menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'El campo :attribute no puede tener más de :value elementos.',
        'file' => 'El archivo :attribute no puede pesar más de :value kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser menor o igual que :value.',
        'string' => 'El campo :attribute no puede tener más de :value caracteres.',
    ],
    'mac_address' => 'El campo :attribute tiene que ser una dirección MAC válida.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El archivo :attribute no puede pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'max_digits' => 'El campo :attribute no puede tener más de :max dígitos.',
    'mimes' => 'El archivo :attribute tiene que ser de tipo: :values.',
    'mimetypes' => 'El archivo :attribute tiene que ser de tipo: :values.',
    'min' => [
        'array' => 'El campo :attribute tiene que tener al menos :min elementos.',
        'file' => 'El archivo :attribute tiene que pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser al menos :min.',
        'string' => 'El campo :attribute tiene que tener al menos :min caracteres.',
    ],
    'min_digits' => 'El campo :attribute tiene que tener al menos :min dígitos.',
    'missing' => 'El campo :attribute no tiene que venir.',
    'missing_if' => 'El campo :attribute no tiene que venir cuando :other es :value.',
    'missing_unless' => 'El campo :attribute no tiene que venir salvo que :other sea :value.',
    'missing_with' => 'El campo :attribute no tiene que venir cuando está :values.',
    'missing_with_all' => 'El campo :attribute no tiene que venir cuando están :values.',
    'multiple_of' => 'El campo :attribute tiene que ser múltiplo de :value.',
    'not_in' => 'El valor elegido en :attribute no es válido.',
    'not_regex' => 'El campo :attribute no tiene un formato válido.',
    'numeric' => 'El campo :attribute tiene que ser un número.',
    'password' => [
        'letters' => 'El campo :attribute tiene que tener al menos una letra.',
        'mixed' => 'El campo :attribute tiene que tener al menos una mayúscula y una minúscula.',
        'numbers' => 'El campo :attribute tiene que tener al menos un número.',
        'symbols' => 'El campo :attribute tiene que tener al menos un símbolo.',
        'uncompromised' => 'El :attribute apareció en una filtración de datos. Elegí otro.',
    ],
    'present' => 'El campo :attribute tiene que venir, aunque sea vacío.',
    'present_if' => 'El campo :attribute tiene que venir cuando :other es :value.',
    'present_unless' => 'El campo :attribute tiene que venir salvo que :other sea :value.',
    'present_with' => 'El campo :attribute tiene que venir cuando está :values.',
    'present_with_all' => 'El campo :attribute tiene que venir cuando están :values.',
    'prohibited' => 'El campo :attribute no se puede cargar acá.',
    'prohibited_if' => 'El campo :attribute no se puede cargar cuando :other es :value.',
    'prohibited_if_accepted' => 'El campo :attribute no se puede cargar cuando se acepta :other.',
    'prohibited_if_declined' => 'El campo :attribute no se puede cargar cuando se rechaza :other.',
    'prohibited_unless' => 'El campo :attribute solo se puede cargar cuando :other es :values.',
    'prohibits' => 'Si cargás :attribute no podés cargar :other.',
    'regex' => 'El campo :attribute no tiene un formato válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_array_keys' => 'El campo :attribute tiene que tener: :values.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_if_accepted' => 'El campo :attribute es obligatorio cuando se acepta :other.',
    'required_if_declined' => 'El campo :attribute es obligatorio cuando se rechaza :other.',
    'required_unless' => 'El campo :attribute es obligatorio salvo que :other sea :values.',
    'required_with' => 'El campo :attribute es obligatorio cuando está :values.',
    'required_with_all' => 'El campo :attribute es obligatorio cuando están :values.',
    'required_without' => 'El campo :attribute es obligatorio si no cargás :values.',
    'required_without_all' => 'El campo :attribute es obligatorio si no cargás ninguno de estos: :values.',
    'same' => 'Los campos :attribute y :other tienen que coincidir.',
    'size' => [
        'array' => 'El campo :attribute tiene que tener :size elementos.',
        'file' => 'El archivo :attribute tiene que pesar :size kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser :size.',
        'string' => 'El campo :attribute tiene que tener :size caracteres.',
    ],
    'starts_with' => 'El campo :attribute tiene que empezar con: :values.',
    'string' => 'El campo :attribute tiene que ser texto.',
    'timezone' => 'El campo :attribute tiene que ser una zona horaria válida.',
    'unique' => 'Ya existe un registro con ese :attribute.',
    'uploaded' => 'El archivo :attribute no se pudo subir.',
    'uppercase' => 'El campo :attribute tiene que estar en mayúsculas.',
    'url' => 'El campo :attribute tiene que ser un link válido.',
    'ulid' => 'El campo :attribute no tiene un formato válido.',
    'uuid' => 'El campo :attribute no tiene un formato válido.',

    /*
    |--------------------------------------------------------------------------
    | Mensajes por campo
    |--------------------------------------------------------------------------
    |
    | Convención "campo.regla". Los mensajes propios de cada módulo viven en
    | el messages() de su FormRequest; acá va solo lo que no tiene dueño.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Nombres de los campos
    |--------------------------------------------------------------------------
    |
    | Reemplazan :attribute (y :other, :values) por la palabra del cliente.
    | Las claves con `*` cubren listas: `celdas.0.nivel`, `adicionales.2.numero`.
    | Al agregar un campo a un FormRequest, agregalo acá (lo controla el test).
    |
    */

    'attributes' => [
        // Comunes
        'nombre' => 'nombre',
        'email' => 'mail',
        'telefono' => 'teléfono',
        'buscar' => 'búsqueda',
        'q' => 'búsqueda',
        'page' => 'página',
        'tipo' => 'tipo',
        'fecha' => 'fecha',
        'descripcion' => 'descripción',
        'estado' => 'estado',

        // Identidad: usuarios, sectores, permisos, ingreso, plazos
        'codigo' => 'código',
        'sector_id' => 'sector',
        'jefe_id' => 'jefatura',
        'activo' => 'activo',
        'urgente_hasta_dias' => 'plazo de Urgente',
        'prioritario_hasta_dias' => 'plazo de Prioritario',
        'celdas' => 'permisos',
        'celdas.*.sector_id' => 'sector',
        'celdas.*.funcion' => 'función',
        'celdas.*.nivel' => 'nivel de acceso',
        'celdas.*.alcance' => 'alcance',

        // Obras: datos de la obra
        'nombre_trabajo' => 'nombre del trabajo',
        'cliente' => 'cliente',
        'planta' => 'planta',
        'responsable_id' => 'responsable',
        'fecha_inicio_prevista' => 'fecha de inicio prevista',
        'fecha_fin_prevista' => 'fecha de fin prevista',
        'carpeta_drive_url' => 'carpeta de Drive',
        'confirmo_pedidos_abiertos' => 'pedidos abiertos',
        'confirmo_pedidos_abiertos.*' => 'pedido abierto',

        // Obras: contactos
        'contactos' => 'contactos',
        'contactos.solicitante' => 'solicitante',
        'contactos.solicitante.nombre' => 'nombre del solicitante',
        'contactos.solicitante.email' => 'mail del solicitante',
        'contactos.solicitante.telefono' => 'teléfono del solicitante',
        'contactos.comprador' => 'comprador',
        'contactos.comprador.nombre' => 'nombre del comprador',
        'contactos.comprador.email' => 'mail del comprador',
        'contactos.comprador.telefono' => 'teléfono del comprador',

        // Obras: presupuestos (adicional suelto, principal del alta y adicionales del alta)
        'numero' => 'N.º de presupuesto',
        'revision' => 'revisión',
        'oferta' => 'oferta técnica',
        'aprobacion' => 'aprobación',
        'aprobacion.tipo' => 'tipo de aprobación',
        'aprobacion.numero' => 'N.º de aprobación',
        'aprobacion.adjunto' => 'documento de aprobación',
        'es_principal' => 'presupuesto principal',
        'presupuesto' => 'presupuesto',
        'presupuesto.numero' => 'N.º de presupuesto',
        'presupuesto.revision' => 'revisión',
        'presupuesto.oferta' => 'oferta técnica',
        'presupuesto.descripcion' => 'descripción',
        'presupuesto.aprobacion' => 'aprobación',
        'presupuesto.aprobacion.tipo' => 'tipo de aprobación',
        'presupuesto.aprobacion.numero' => 'N.º de aprobación',
        'presupuesto.aprobacion.adjunto' => 'documento de aprobación',
        'adicionales' => 'adicionales',
        'adicionales.*.numero' => 'N.º de presupuesto del adicional',
        'adicionales.*.revision' => 'revisión del adicional',
        'adicionales.*.oferta' => 'oferta técnica del adicional',
        'adicionales.*.descripcion' => 'descripción del adicional',
        'adicionales.*.aprobacion' => 'aprobación del adicional',
        'adicionales.*.aprobacion.tipo' => 'tipo de aprobación del adicional',
        'adicionales.*.aprobacion.numero' => 'N.º de aprobación del adicional',
        'adicionales.*.aprobacion.adjunto' => 'documento de aprobación del adicional',

        // Obras: montos y documentos
        'monto_obra' => 'monto de obra',
        'modo' => 'forma de carga',
        'montos' => 'montos por rubro',
        'montos.*' => 'monto del rubro',
        'total' => 'total',
        'adjuntos' => 'adjuntos',
        'adjuntos.*' => 'adjunto',

        // Proveedores
        'cuit' => 'CUIT',
        'rubro_habitual' => 'rubro habitual',
        'excluir_id' => 'proveedor a excluir',

        // Adjuntos
        'contexto' => 'lugar del adjunto',
        'archivo' => 'archivo',
        'url' => 'link',
    ],

    /*
    |--------------------------------------------------------------------------
    | Valores
    |--------------------------------------------------------------------------
    |
    | Reemplazan los valores técnicos que aparecen en :value y :values.
    |
    */

    'values' => [
        'modo' => [
            'por_rubro' => 'por rubro',
            'solo_total' => 'solo total',
        ],
    ],

];
