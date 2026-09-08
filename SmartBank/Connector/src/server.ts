import express from 'express';
import cors from 'cors';
import { env } from './config/env';
import { healthRoutes } from './api/health.routes';
import { errorHandler } from './middleware/errorHandler';
import { usersRoutes } from './api/users.routes';
import { paymentsRoutes } from './api/payments.routes';
import { adminRoutes } from './api/admin.routes';

const app = express();
app.use(cors());
app.use(express.json());

app.use('/', healthRoutes);
app.use('/v1/connect/users', usersRoutes);
app.use('/v1/connect/payment-requests', paymentsRoutes);
app.use('/v1/connect/admin', adminRoutes);

app.use(errorHandler);

if (require.main === module) {
  app.listen(env.PORT, () => {
    console.log(`Connector running on port ${env.PORT}`);
  });
}

export default app;
