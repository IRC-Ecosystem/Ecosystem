import React from 'react';
import { Menu } from 'lucide-react';
import { ApiHealthIndicator } from '../ui/ApiHealthIndicator';
import { NotificationBell } from './NotificationBell';

interface TopbarProps {
  onMenuClick: () => void;
}

export const Topbar: React.FC<TopbarProps> = ({ onMenuClick }) => {
  return (
    <header className="topbar">
      <div className="topbar__left">
        <button onClick={onMenuClick} className="mobile-menu-btn" aria-label="Open navigation">
          <Menu size={24} />
        </button>
      </div>

      <div className="topbar__right">
        <NotificationBell />
        <ApiHealthIndicator />
      </div>
    </header>
  );
};
