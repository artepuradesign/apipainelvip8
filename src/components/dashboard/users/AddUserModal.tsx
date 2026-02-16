
import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { User as UserIcon, Mail, CreditCard, FileText, Save, X, UserPlus } from 'lucide-react';

interface AddUserModalProps {
  isOpen: boolean;
  onClose: () => void;
  newUser: {
    username: string;
    name: string;
    email: string;
    role: 'assinante' | 'suporte';
    plan: string;
    balance: number;
    cpf: string;
    phone: string;
    address: string;
    notes: string;
  };
  setNewUser: (user: any) => void;
  onSubmit: () => void;
}

const AddUserModal = ({ isOpen, onClose, newUser, setNewUser, onSubmit }: AddUserModalProps) => {
  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="w-[95vw] max-w-2xl p-0 gap-0 overflow-hidden">
        {/* Header */}
        <DialogHeader className="px-5 pt-5 pb-4 border-b border-border bg-muted/30">
          <DialogTitle className="text-lg font-semibold flex items-center gap-2">
            <UserPlus className="h-5 w-5 text-primary" />
            Novo Usuário
          </DialogTitle>
          <p className="text-xs text-muted-foreground mt-1">Preencha os dados para criar um novo usuário</p>
        </DialogHeader>

        <div className="p-5 space-y-5 max-h-[70vh] overflow-y-auto">
          {/* Seção: Dados Pessoais */}
          <div>
            <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Dados Pessoais</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="add-username" className="text-xs flex items-center gap-1.5">
                  <UserIcon className="h-3 w-3" /> Nome de Usuário *
                </Label>
                <Input
                  id="add-username"
                  className="h-9 text-sm"
                  value={newUser.username}
                  onChange={(e) => setNewUser({ ...newUser, username: e.target.value })}
                  placeholder="Ex: joao123"
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="add-name" className="text-xs flex items-center gap-1.5">
                  <UserIcon className="h-3 w-3" /> Nome Completo *
                </Label>
                <Input
                  id="add-name"
                  className="h-9 text-sm"
                  value={newUser.name}
                  onChange={(e) => setNewUser({ ...newUser, name: e.target.value })}
                  placeholder="Ex: João Silva"
                />
              </div>
            </div>
          </div>

          {/* Seção: Contato */}
          <div>
            <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Contato</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="add-email" className="text-xs flex items-center gap-1.5">
                  <Mail className="h-3 w-3" /> E-mail *
                </Label>
                <Input
                  id="add-email"
                  type="email"
                  className="h-9 text-sm"
                  value={newUser.email}
                  onChange={(e) => setNewUser({ ...newUser, email: e.target.value })}
                  placeholder="Ex: joao@email.com"
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="add-cpf" className="text-xs flex items-center gap-1.5">
                  <CreditCard className="h-3 w-3" /> CPF
                </Label>
                <Input
                  id="add-cpf"
                  className="h-9 text-sm"
                  value={newUser.cpf}
                  onChange={(e) => setNewUser({ ...newUser, cpf: e.target.value })}
                  placeholder="Ex: 123.456.789-00"
                />
              </div>
            </div>
          </div>

          {/* Seção: Configurações */}
          <div>
            <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Configurações</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="add-role" className="text-xs">Tipo de Usuário</Label>
                <Select value={newUser.role} onValueChange={(value: any) => setNewUser({ ...newUser, role: value })}>
                  <SelectTrigger id="add-role" className="h-9 text-sm">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="assinante">Assinante</SelectItem>
                    <SelectItem value="suporte">Suporte</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="add-balance" className="text-xs flex items-center gap-1.5">
                  <CreditCard className="h-3 w-3" /> Saldo Inicial
                </Label>
                <Input
                  id="add-balance"
                  type="number"
                  className="h-9 text-sm"
                  value={newUser.balance}
                  onChange={(e) => setNewUser({ ...newUser, balance: parseFloat(e.target.value) || 0 })}
                  placeholder="0.00"
                />
              </div>
            </div>
          </div>

          {/* Seção: Observações */}
          <div>
            <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Observações</h3>
            <div className="space-y-1.5">
              <Label htmlFor="add-notes" className="text-xs flex items-center gap-1.5">
                <FileText className="h-3 w-3" /> Notas
              </Label>
              <Input
                id="add-notes"
                className="h-9 text-sm"
                value={newUser.notes}
                onChange={(e) => setNewUser({ ...newUser, notes: e.target.value })}
                placeholder="Observações sobre o usuário..."
              />
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex justify-end gap-2 px-5 py-4 border-t border-border bg-muted/30">
          <Button size="sm" variant="outline" onClick={onClose} className="gap-1.5">
            <X className="h-3.5 w-3.5" />
            Cancelar
          </Button>
          <Button size="sm" onClick={onSubmit} className="gap-1.5">
            <Save className="h-3.5 w-3.5" />
            Criar Usuário
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default AddUserModal;
