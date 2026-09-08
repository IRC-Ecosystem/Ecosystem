import React, { useEffect, useState } from 'react';
import { NotificationsApi, Notification } from '../../api/notifications.api';
import { Card } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Bell, CheckCircle, Info, Key, ShieldCheck } from 'lucide-react';

function formatCountdown(expiresAt?: string) {
  if (!expiresAt) return null;

  const remainingSeconds = Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000));
  const minutes = Math.floor(remainingSeconds / 60).toString().padStart(2, '0');
  const seconds = (remainingSeconds % 60).toString().padStart(2, '0');

  return `${minutes}:${seconds}`;
}

export const InboxPage: React.FC = () => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  const [, setClockTick] = useState(0);

  const fetchNotifications = async () => {
    try {
      const res = await NotificationsApi.list();
      if (res.ok && res.envelope.data) {
        setNotifications(res.envelope.data.items);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchNotifications();
    const interval = setInterval(fetchNotifications, 10000);
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    const interval = setInterval(() => setClockTick((tick) => tick + 1), 1000);
    return () => clearInterval(interval);
  }, []);

  const handleMarkRead = async (id: string) => {
    const item = notifications.find(n => n.id === id);
    if (!item || item.read_at) return;

    // Optimistic update
    setNotifications(prev => prev.map(n => n.id === id ? { ...n, read_at: new Date().toISOString() } : n));
    await NotificationsApi.markRead(id);
  };

  const handleReadAll = async () => {
    setNotifications(prev => prev.map(n => ({ ...n, read_at: new Date().toISOString() })));
    await NotificationsApi.readAll();
  };

  const getIcon = (type: string) => {
    if (type.includes('OTP')) return <Key className="text-blue-500" />;
    if (type.includes('SETTLED')) return <CheckCircle className="text-green-500" />;
    if (type.includes('LINKED')) return <ShieldCheck className="text-green-500" />;
    return <Info className="text-gray-500" />;
  };

  return (
    <div className="page-stack">
      <div className="page-header__row">
        <div>
          <h1 className="page-header__title">Inbox</h1>
          <p className="page-header__description">Pusat notifikasi dan kode OTP Anda.</p>
        </div>
        <Button variant="secondary" onClick={handleReadAll}>Tandai Semua Dibaca</Button>
      </div>

      <div className="bento-grid" style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
        {loading && notifications.length === 0 ? (
          <div>Loading...</div>
        ) : notifications.length === 0 ? (
          <div style={{ padding: '3rem', textAlign: 'center', color: 'var(--text-muted)' }}>
            <Bell size={48} style={{ margin: '0 auto 1rem', opacity: 0.5 }} />
            <p>Tidak ada notifikasi saat ini.</p>
          </div>
        ) : (
          notifications.map(n => (
            <Card 
              key={n.id} 
              className={`p-4 cursor-pointer transition-colors ${!n.read_at ? 'bg-blue-50 border-blue-200' : ''}`}
              onClick={() => handleMarkRead(n.id)}
              style={{ borderLeft: !n.read_at ? '4px solid var(--color-info-500)' : undefined }}
            >
              <div style={{ display: 'flex', gap: '1rem' }}>
                <div style={{ padding: '0.5rem', background: 'var(--surface)', borderRadius: '8px', border: '1px solid var(--border)' }}>
                  {getIcon(n.type)}
                </div>
                <div style={{ flex: 1 }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.25rem' }}>
                    <h3 style={{ fontWeight: 600, margin: 0 }}>{n.title}</h3>
                    <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>
                      {new Date(n.created_at).toLocaleString('id-ID')}
                    </span>
                  </div>
                  <p style={{ color: 'var(--text-secondary)', margin: 0 }}>{n.body}</p>
                  
                  {n.payload?.otp_code && (
                    <div style={{ marginTop: '1rem', padding: '1rem', background: 'var(--surface)', border: '1px solid var(--border-focus)', borderRadius: '8px', textAlign: 'center' }}>
                      <p style={{ fontSize: '0.9rem', color: 'var(--text-secondary)', marginBottom: '0.5rem' }}>Kode OTP Anda:</p>
                      <div style={{ fontSize: '2rem', fontWeight: 'bold', letterSpacing: '0.5rem', fontFamily: 'monospace' }}>
                        {n.payload.otp_code}
                      </div>
                      <p style={{ fontSize: '0.8rem', color: 'var(--color-danger-600)', marginTop: '0.5rem' }}>
                        Berlaku {formatCountdown(String(n.payload.expires_at)) ?? '00:00'} lagi
                      </p>
                    </div>
                  )}
                </div>
              </div>
            </Card>
          ))
        )}
      </div>
    </div>
  );
};
