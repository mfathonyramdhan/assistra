var fs = require('fs');
const request = require("request");
var body = JSON.parse(fs.readFileSync('body.json', 'utf8'));

var options = {
    method: 'POST',
    uri: 'https://api.pagar.me/core/v5/orders',
    headers: {
        'Authorization': 'Basic ' + Buffer.from("sk_test_*:").toString('base64'),
        'Content-Type': 'application/json'
    },
    json: body
};

request(options, function (error, response, body) {
    console.log(response.body);
});