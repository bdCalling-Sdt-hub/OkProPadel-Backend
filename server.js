import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';

const app = express();
const server = createServer(app);
const io = new Server(server, {
    cors: {
        origin: '*',
    },
});
io.on('connection', (socket) => {
    console.log(`User connected: ${socket.id}`);

    socket.on('group-message', ({senderId,message }) => {
        console.log(`Group message : ${message}`);
        io.to(senderId).emit('group-message', {
            senderId: socket.id,
            message,
        });
    });
    socket.on('private-message', ({ recipientId, message }) => {
        console.log(` User ${recipientId}: ${message}`);
        io.to(recipientId).emit('private-message', {
            senderId: socket.id,
            message,
        });
    });
    socket.on('disconnect', () => {
        console.log(`User disconnected: ${socket.id}`);
    });
});
const PORT = 3000;
server.listen(PORT, () => {
    console.log(`Socket.IO server running at http://192.168.12.160:${PORT}`);
});
