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
const groups = {};
io.on('connection', (socket) => {
    console.log(`User connected: ${socket.id}`);

    socket.on('private-message', ({ recipientId, message }) => {
        console.log(` User ${recipientId}: ${message}`);
        io.to(recipientId).emit('private-message', {
            senderId: socket.id,
            message,
        });
    });
    socket.on('join_group', ({ userId, groupId }) => {
        socket.join(groupId);
        if (!groups[groupId]) groups[groupId] = [];
        groups[groupId].push(userId);
        console.log(`User ${userId} joined group ${groupId}`);
      });

      // Handle group message
      socket.on('group_message', ({ groupId, senderId, message }) => {
        io.to(groupId).emit('group_message', { senderId, message });
        console.log(`Message in group ${groupId} from ${senderId}: ${message}`);
      });
    socket.on('disconnect', () => {
        console.log(`User disconnected: ${socket.id}`);
    });
});
const PORT = 3000;
server.listen(PORT, () => {
    console.log(`Socket.IO server running at http://192.168.12.160:${PORT}`);
});
