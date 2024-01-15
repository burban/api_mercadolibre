 // Call the dataTables jQuery plugin
 var ViewController = {
     formatter: new Intl.NumberFormat('es-CL', {
         style: 'currency',
         currency: 'CLP'
     }),
     binding: function() {
         var self = ViewController;
         $(".card-footer").find("button").bind("click", {}, self.operationType ? this.sincronizar : this.openModalAdd);
         $("#addEmail").bind("click", {}, this.add);
     },
     moneyCLP: function() {
         // Obtenemos la definicion de la funcion original
         let prop = Object.getOwnPropertyDescriptor(Intl.NumberFormat.prototype, 'format');
         // Sobrescribimos el mtodo "format"
         Object.defineProperty(Intl.NumberFormat.prototype, 'format', {
             get: function() {
                 return function(value) {
                     let fn = prop.get.call(this), // Recuperamos la funcion "formateadora" original
                         opts = this.resolvedOptions(); // Obtenemos las opciones de "formateo"

                     // Solo cambiamos el formateo cuando es moneda en espaol y el numero es >= 1.000 o menor a 10.000
                     if (opts.style == 'currency' && opts.locale.substr(0, 2) == 'es' && opts.numberingSystem == 'latn' && value >= 1000 && value < 10000) {
                         let num = fn(10000), // -> [pre]10[sep]000[sub]
                             pre = num.substr(0, num.indexOf('10')),
                             sep = num.substr(num.indexOf('10') + 2, 1),
                             sub = num.substr(num.indexOf('000') + 3);
                         num = value.toString();
                         return pre + num.slice(0, 1) + sep + num.slice(1) + sub;
                     }
                     // Sino devolvemos el nmero formateado normalmente
                     return fn(value);
                 };
             },
         });
     },
     search: function(e) {
         var self = ViewController;
         var data = self.operationType ? { inmuebles: "success" } : { correos: "success" };
         $.ajax({
             type: "post", //request type,
             dataType: 'json',
             data: data,
             url: "api.php",
             beforeSend: function() {
                 $("#vs-loading").removeClass("d-none");
                 $(".sincroniza-status").remove();
             },
             success: function(response) {
                 console.log(response.data);
                 self.preInitTable();
                 var tbody = $(self.table + " tbody");
                 tbody.empty();
                 self.initTable(response.data);
             },
             error: function(XMLHttpRequest, textStatus, errorThrown) {
                 console.log(XMLHttpRequest.responseText);
             }
         }).always(function() {
             $("#vs-loading").addClass("d-none");
         }).fail(function() {
             alert('Ha ocurrido un error');
         });
     },
     openModalAdd: function() {
         $("#addNewEmail").find("input").val("");
         $("#addNewEmail").modal("show");
     },
     sincronizar: function() {
         var self = ViewController;
         $.ajax({
             type: "post", //request type,
             dataType: 'json',
             data: { addInmuebles: "success" },
             url: "api.php",
             beforeSend: function() {
                 $("#vs-loading").removeClass("d-none");
                 $(".sincroniza-status").remove();
             },
             success: function(response) {
                 $(".card-body").prepend('<div class="alert alert-success sincroniza-status" role="alert">Sincronizacion Exitosa</div>');
                 //console.log(response);
             },
             error: function(XMLHttpRequest, textStatus, errorThrown) {
                 $(".card-body").prepend('<div class="alert alert-danger sincroniza-status" role="alert">Sincronizacion Fallida</div>');
                 console.log(XMLHttpRequest.responseText);
             }
         }).always(function() {
             $("#vs-loading").addClass("d-none");
         });
     },
     update: function(data) {
         var self = ViewController;
         $.ajax({
             type: "post", //request type,
             dataType: 'json',
             data: { updateCorreos: data },
             url: "api.php",
             beforeSend: function() {
                 $("#vs-loading").removeClass("d-none");
                 $(".sincroniza-status").remove();
             },
             success: function(response) {
                 self.search();
             },
             error: function(XMLHttpRequest, textStatus, errorThrown) {
                 console.log(XMLHttpRequest.responseText);
                 alert(XMLHttpRequest.responseText);
             }
         }).always(function() {
             $("#vs-loading").addClass("d-none");
         });
     },
     elimina: function(row) {
         if (!confirm("¿Esta seguro de eliminar el registro?")) {
             return;
         }

         var self = ViewController;
         var data = $(self.table).DataTable().rows().data();
         var out = [];
         for (var i = 0; i < data.length; i++) {
             if (data[i].email != row.email) {
                 out.push(data[i].email);
             }
         }
         self.update(out.join(";"));
     },
     add: function() {
         var self = ViewController;
         var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
         var correo = $("#email-text").val();
         if (!regex.test(correo)) {
             $("#email-text").css("border", "2px #ff0000 solid");
             return;
         } else {
             $("#email-text").removeAttr('style');
         }
         var out = [];
         var data = $(self.table).DataTable().rows().data();
         for (var i = 0; i < data.length; i++) {
             out.push(data[i].email);
         }
         out.push(correo);
         $("#addNewEmail").modal("hide");
         self.update(out.join(";"));
     },
     preInitTable: function() {
         var self = ViewController;
         if ($.fn.dataTable.isDataTable(self.table)) {
             $(self.table).DataTable().destroy();
         }
     },
     operationType: location.pathname.indexOf("index.php") != -1 ? true : false,
     table: location.pathname.indexOf("index.php") != -1 ? "#data-meli" : "#data-correos",
     initTable: function(data) {
         var self = ViewController;
         var options = {
             /*      dom: 'Bfrtip',
             buttons: [
                 'copyHtml5',
                 'excelHtml5',
                 'csvHtml5',
                 'pdfHtml5'
             ],*/
             paging: true,
             info: true,
             searching: true,
             ordering: true,
             pageLength: 10,
             autoWidth: false,
             stateSave: false,
             stateDuration: -1,
             bLengthChange: false,
             data: data,
             initComplete: function(settings, json) {},
             order: [
                 [0, 'asc']
             ],
             language: {
                 "decimal": "",
                 "emptyTable": "No existen datos",
                 "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                 "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                 "infoFiltered": "(filtrado de _MAX_ registros)",
                 "infoPostFix": "",
                 "thousands": ",",
                 "lengthMenu": "Mostrar _MENU_ registros",
                 "loadingRecords": "Cargando...",
                 "processing": "Procesando...",
                 "search": "Buscar:",
                 "zeroRecords": "No se encontraron registros",
                 "paginate": {
                     "first": "Primero",
                     "last": "&Uacute;ltimo",
                     "next": "Siguiente",
                     "previous": "Anterior"
                 },
                 "aria": {
                     "sortAscending": ": activar para ordenar columna de forma ascendente",
                     "sortDescending": ": activar para ordenar columna de forma descendente"
                 }
             },
             columns: [{
                 "data": "email",
                 "visible": true,
                 "orderable": true
             }, {
                 "data": "email",
                 "visible": true,
                 "orderable": true
             }]
         };

         if (self.operationType) {
             options.columns = [{
                     "data": "title",
                     "visible": true,
                     "orderable": true
                 },
                 {
                     "data": "attributes",
                     "visible": true,
                     "orderable": true,
                     "render": function(d) {
                         var a = d.find(x => x.id === 'OPERATION');
                         if (a) { a = a.value_name; } else { a = "sin informacion" };
                         return a;
                     }
                 },
                 {
                     "data": "attributes",
                     "visible": true,
                     "orderable": true,
                     "render": function(d) {
                         var a = d.find(x => x.id === 'PROPERTY_TYPE');
                         if (a) { a = a.value_name; } else { a = "sin informacion" };
                         return a;
                     }
                 },
                
                 {
                     "data": "location",
                     "visible": true,
                     "orderable": true,
                     "render": function(d) {
                         return d.address_line;
                     }
                 },
                 {
                     "data": "location",
                     "visible": true,
                     "orderable": true,
                     "render": function(d) {
                         return d.city.name;
                     }
                 },
                 {
                     "data": "location",
                     "visible": true,
                     "orderable": true,
                     "render": function(d) {
                         return d.state.name;
                     }
                 },
                 {
                     "data": "permalink",
                     "visible": true,
                     "orderable": true,
                     "render": function(d) { return ""; },
                     "createdCell": function(td, cellData, rowData, row, col) {
                         $(td).append('<a target="_blank" title="ver publicación" href="' + cellData + '"><i class="fa fa-eye"></i></a>');
                     }

                 }
             ]
         } else {
             options.columns = [{
                     "data": "email",
                     "visible": true,
                     "orderable": true
                 },
                 {
                     "data": "email",
                     "visible": true,
                     "orderable": true,
                     "render": function(d) { return ""; },
                     "createdCell": function(td, cellData, rowData, row, col) {
                         $(td).append('<a title="Eliminar registro"><i class="fa fa-trash"></i></a>');
                         $(td).on("click", function() {
                             self.elimina(rowData)
                         });

                     }

                 }
             ]
         }

         $(self.table).DataTable(options);
         //self.table = $("#data-meli").DataTable(options);
     },
     init: function(options) {
         var self = this;
         self.binding();
         self.search('');
     }
 }

 $(document).ready(function() {
     var options = { page: location.pathname.indexOf("index.php") != -1 ? true : false };
     ViewController.init(options);
 });