import React, { useEffect, useState } from 'react';
import { Bell } from 'lucide-react';
import { Link } from 'react-router-dom';
import { NotificationsApi } from '../../api/notifications.api';

export const NotificationBell: React.FC = () => {
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    const fetchCount = async () => {
      try {
        const res = await NotificationsApi.unreadCount();
        if (res.ok && res.envelope.data) {
          setUnreadCount(res.envelope.data.count);
        }
      } catch (e) {
        // ignore
      }
    };

    fetchCount();
    const interval = setInterval(fetchCount, 10000);
    return () => clearInterval(interval);
  }, []);

  return (
    <Link to="/inbox" style={{ position: 'relative', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: '40px', height: '40px', borderRadius: '8px', color: 'var(--text-secondary)' }}>
      <Bell size={20} />
      {unreadCount > 0 && (
        <span style={{
          position: 'absolute',
          top: '4px',
          right: '4px',
          background: 'var(--color-danger-500)',
          color: 'white',
          fontSize: '10px',
          fontWeight: 'bold',
          padding: '2px 6px',
          borderRadius: '10px',
          lineHeight: 1
        }}>
          {unreadCount > 99 ? '99+' : unreadCount}
        </span>
      )}
    </Link>
  );
};
