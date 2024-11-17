# Callbacks
The SEP's provides different callback mechanisms to notify the receiver about the status of the transaction or customer.
Under the `tools/callback-handler` you can find a simple server that listens for incoming callbacks and logs them to the console.
It validates the signature of the callback and logs the payload to the console.

## Run the callback handler server
To run the callback handler server, follow these steps:
```bash
cd tools/callback-handler
npp install
node callback-handler-server.js
```