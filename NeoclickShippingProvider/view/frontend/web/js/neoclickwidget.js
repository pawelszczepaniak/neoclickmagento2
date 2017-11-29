require([
    "jquery"
], function($){
//<![CDATA[
	 $(document).ready(function() {
	        NeoClick.init({
	            appId: "6210638810678563195"
	          },
	          function(response) {

	          });

	        NeoClick.setBasket({
	          "currency": "PLN",
	          "type": "real",
	          "correlationId": "AAAA-123456789",
	          "articles": [{
	              "id": "productA",
	              "name": "T-shirt",
	              "price": 2500,
	              "quantity": 1,
	              "dimensions": {
	                "width": 50,
	                "height": 30,
	                "depth": 5,
	                "weight": 300
	              }
	            },
	            {
	              "id": "productB",
	              "name": "Kubek biały",
	              "price": 300,
	              "quantity": 5,
	              "dimensions": {
	                "width": 20,
	                "height": 20,
	                "depth": 30,
	                "weight": 500
	              }
	            }
	          ],
	          "dimensions": {
	            "width": 300,
	            "height": 400,
	            "depth": 500,
	            "weight": 600
	          },
	          "signature": ""
	        });
	//]]>
	});

//]]>
});