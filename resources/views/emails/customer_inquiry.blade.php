<!DOCTYPE html>
<html>
<head>
    <title>Customer Inquiry</title>
</head>
<body>
    <h1>New Customer Inquiry</h1>
    <p><strong>Quantity of products required:</strong> {{ $data['quantity'] }}</p>
    <p><strong>Products requested:</strong> {{ $data['products'] }}</p>
    <p><strong>Name:</strong> {{ $data['name'] }}</p>
    <p><strong>Email:</strong> {{ $data['email'] }}</p>
    <p><strong>Company/Organization:</strong> {{ $data['company'] }}</p>
    <p><strong>Phone:</strong> {{ $data['phone'] }}</p>
    <p><strong>File:</strong> {{ $data['file'] }}</p>
</body>
</html>